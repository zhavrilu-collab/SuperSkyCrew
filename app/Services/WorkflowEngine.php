<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\OrganizationRole;
use App\Enums\RequestActionType;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Enums\TimeEntryStatus;
use App\Models\AbsenceCode;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\Punch;
use App\Models\ShiftOverride;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRequest;
use App\Models\WorkflowRequestAction;
use App\Notifications\WorkflowRequestNotification;
use App\Rules\ValidOib;
use App\Support\PersonalDataChange;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkflowEngine
{
    public function __construct(
        private readonly LeaveService $leave,
        private readonly OrganizationRbacService $rbac,
        private readonly HrSetupService $setup,
        private readonly PeriodLockService $locks,
        private readonly ClockService $clock,
        private readonly AuditService $audit,
        private readonly DocumentFillService $documents,
        private readonly ShiftResolver $shifts,
    ) {}

    /**
     * @param  array{
     *     from?: string|null,
     *     to?: string|null,
     *     note?: string|null,
     *     absence_code?: string|null,
     *     minutes?: int|null,
     *     punch_id?: int|null,
     *     occurred_at?: string|null,
     *     occurred_on?: string|null,
     *     first_name?: string|null,
     *     last_name?: string|null,
     *     oib?: string|null,
     *     gender?: string|null,
     *     date_of_birth?: string|null,
     *     citizenship?: string|null,
     *     residence?: string|null
     * }  $input
     */
    public function submit(Organization $organization, Person $person, User $actor, RequestType $type, array $input): WorkflowRequest
    {
        $this->setup->provision($organization);

        return match ($type) {
            RequestType::Overtime => $this->submitOvertime($organization, $person, $actor, $input),
            RequestType::PunchCorrection => $this->submitPunchCorrection($organization, $person, $actor, $input),
            RequestType::PersonalDataChange => $this->submitPersonalData($organization, $person, $actor, $input),
            RequestType::ShiftSwap => $this->submitShiftSwap($organization, $person, $actor, $input),
            default => $this->submitLeave($organization, $person, $actor, $type, $input),
        };
    }

    /**
     * @param  array{from?: string|null, to?: string|null, note?: string|null, absence_code?: string|null}  $input
     */
    private function submitLeave(Organization $organization, Person $person, User $actor, RequestType $type, array $input): WorkflowRequest
    {
        $from = Carbon::parse($input['from'] ?? '')->startOfDay();
        $to = Carbon::parse($input['to'] ?? $input['from'] ?? '')->startOfDay();
        $dates = WorkingDays::dates($from, $to);
        $days = count($dates);

        if ($days === 0) {
            throw ValidationException::withMessages([
                'to' => 'U odabranom rasponu nema radnih dana.',
            ]);
        }

        $code = $input['absence_code'] ?? $type->defaultAbsenceCode();
        if ($type === RequestType::LeaveOther && blank($code)) {
            throw ValidationException::withMessages([
                'absence_code' => 'Odaberite šifru odsutnosti.',
            ]);
        }

        if ($type === RequestType::LeaveAnnual) {
            $code = 'GO';
            $this->leave->assertAvailable($person, $days, (int) $from->year);
        }

        $this->assertNoOverlap($person, $dates, $code);
        $this->assertWritableDays($person, $dates);

        return $this->createPending($organization, $person, $actor, $type, $this->approvalPath($person, $type, $days), [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days' => $days,
            'dates' => $dates,
            'note' => $input['note'] ?? null,
            'absence_code' => $code,
        ], $input['note'] ?? null);
    }

    /**
     * @param  array{from?: string|null, minutes?: int|null, note?: string|null}  $input
     */
    private function submitOvertime(Organization $organization, Person $person, User $actor, array $input): WorkflowRequest
    {
        if (blank($input['from'] ?? null)) {
            throw ValidationException::withMessages(['from' => 'Odaberite datum prekovremenog.']);
        }

        $minutes = (int) ($input['minutes'] ?? 0);
        if ($minutes < 15 || $minutes > 720) {
            throw ValidationException::withMessages([
                'minutes' => 'Prekovremeni mora biti između 15 i 720 minuta.',
            ]);
        }

        if (blank($input['note'] ?? null)) {
            throw ValidationException::withMessages(['note' => 'Obrazložite prekovremeni rad.']);
        }

        $day = Carbon::parse($input['from'])->startOfDay();
        $this->locks->assertWritable($person, $day);

        $absent = TimeEntry::query()
            ->where('person_id', $person->id)
            ->whereDate('work_date', $day->toDateString())
            ->whereNotNull('absence_code')
            ->exists();

        if ($absent) {
            throw ValidationException::withMessages([
                'from' => 'Taj dan je već označen kao odsutnost.',
            ]);
        }

        return $this->createPending($organization, $person, $actor, RequestType::Overtime, $this->approvalPath($person, RequestType::Overtime, 1), [
            'from' => $day->toDateString(),
            'to' => $day->toDateString(),
            'days' => 1,
            'dates' => [$day->toDateString()],
            'minutes' => $minutes,
            'note' => $input['note'],
        ], $input['note']);
    }

    /**
     * @param  array{punch_id?: int|null, occurred_at?: string|null, note?: string|null}  $input
     */
    private function submitPunchCorrection(Organization $organization, Person $person, User $actor, array $input): WorkflowRequest
    {
        if (blank($input['note'] ?? null)) {
            throw ValidationException::withMessages(['note' => 'Ispravak zahtijeva razlog.']);
        }

        $punch = Punch::query()
            ->where('person_id', $person->id)
            ->where('id', (int) ($input['punch_id'] ?? 0))
            ->first();

        if ($punch === null) {
            throw ValidationException::withMessages(['punch_id' => 'Odaberite prijavu koju ispravljate.']);
        }

        if ($punch->corrections()->exists()) {
            throw ValidationException::withMessages(['punch_id' => 'Ta prijava je već ispravljena.']);
        }

        if (blank($input['occurred_at'] ?? null)) {
            throw ValidationException::withMessages(['occurred_at' => 'Unesite točno vrijeme.']);
        }

        $occurredAt = Carbon::parse($input['occurred_at'])->timezone(config('app.timezone'));
        $this->locks->assertWritable($person, $punch->occurred_at_device);
        $this->locks->assertWritable($person, $occurredAt);

        $from = $punch->occurred_at_device->timezone(config('app.timezone'))->toDateString();
        $to = $occurredAt->toDateString();

        return $this->createPending($organization, $person, $actor, RequestType::PunchCorrection, $this->approvalPath($person, RequestType::PunchCorrection, 1), [
            'from' => $from,
            'to' => $to,
            'days' => 1,
            'dates' => array_values(array_unique([$from, $to])),
            'punch_id' => $punch->id,
            'occurred_at' => $occurredAt->toDateTimeString(),
            'original_occurred_at' => $punch->occurred_at_device->timezone(config('app.timezone'))->toDateTimeString(),
            'original_type' => $punch->type->value,
            'note' => $input['note'],
        ], $input['note']);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function submitPersonalData(Organization $organization, Person $person, User $actor, array $input): WorkflowRequest
    {
        $input['occurred_on'] = $input['occurred_on'] ?? $input['from'] ?? null;

        $validator = Validator::make($input, [
            'occurred_on' => ['required', 'date', 'before_or_equal:today'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'oib' => ['nullable', 'digits:11', new ValidOib],
            'gender' => ['nullable', Rule::in(['m', 'z', 'x'])],
            'date_of_birth' => ['nullable', 'date'],
            'citizenship' => ['nullable', 'string', 'max:80'],
            'residence' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $pending = WorkflowRequest::query()
            ->where('person_id', $person->id)
            ->where('type', RequestType::PersonalDataChange)
            ->where('status', RequestStatus::Pending)
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'type' => 'Već imate otvorenu prijavu promjene podataka.',
            ]);
        }

        $changes = PersonalDataChange::diff($person, $input);

        if ($changes === []) {
            throw ValidationException::withMessages([
                'residence' => 'Niste unijeli nijednu promjenu.',
            ]);
        }

        foreach (['first_name', 'last_name'] as $required) {
            if (isset($changes[$required]) && blank($changes[$required]['to'])) {
                throw ValidationException::withMessages([
                    $required => 'Ime i prezime ne smiju biti prazni.',
                ]);
            }
        }

        $occurredOn = Carbon::parse($input['occurred_on'])->timezone(config('app.timezone'))->startOfDay();
        $today = now()->timezone(config('app.timezone'))->startOfDay();
        $daysSince = (int) round($occurredOn->diffInDays($today, false));
        $late = $daysSince > 8;

        return $this->createPending(
            $organization,
            $person,
            $actor,
            RequestType::PersonalDataChange,
            $this->approvalPath($person, RequestType::PersonalDataChange, 1),
            [
                'from' => $occurredOn->toDateString(),
                'to' => $occurredOn->toDateString(),
                'days' => 0,
                'occurred_on' => $occurredOn->toDateString(),
                'late' => $late,
                'days_since' => $daysSince,
                'changes' => $changes,
                'note' => $input['note'] ?? null,
            ],
            $input['note'] ?? null,
        );
    }

    /**
     * @param  array{from?: string|null, counterpart_id?: int|null, note?: string|null}  $input
     */
    private function submitShiftSwap(Organization $organization, Person $person, User $actor, array $input): WorkflowRequest
    {
        if (blank($input['from'] ?? null)) {
            throw ValidationException::withMessages(['from' => 'Odaberite dan zamjene.']);
        }

        $day = Carbon::parse($input['from'])->timezone(config('app.timezone'))->startOfDay();
        $this->locks->assertWritable($person, $day);

        $counterpart = Person::query()
            ->forOrganization($organization)
            ->where('id', (int) ($input['counterpart_id'] ?? 0))
            ->first();

        if ($counterpart === null || (int) $counterpart->id === (int) $person->id) {
            throw ValidationException::withMessages(['counterpart_id' => 'Odaberite kolegu za zamjenu.']);
        }

        if (! $counterpart->isClockEligible()) {
            throw ValidationException::withMessages(['counterpart_id' => 'Kolega nije u statusu koji prima smjene.']);
        }

        $own = $this->shifts->forPersonOn($person, $day);
        $theirs = $this->shifts->forPersonOn($counterpart, $day);
        if ($own === null && $theirs === null) {
            throw ValidationException::withMessages(['from' => 'Nijedna strana nema smjenu na taj dan.']);
        }

        return $this->createPending(
            $organization,
            $person,
            $actor,
            RequestType::ShiftSwap,
            $this->approvalPath($person, RequestType::ShiftSwap, 1),
            [
                'from' => $day->toDateString(),
                'to' => $day->toDateString(),
                'days' => 1,
                'dates' => [$day->toDateString()],
                'counterpart_id' => $counterpart->id,
                'counterpart_name' => $counterpart->fullName(),
                'own_shift_id' => $own?->id,
                'counterpart_shift_id' => $theirs?->id,
                'note' => $input['note'] ?? null,
            ],
            $input['note'] ?? null,
        );
    }

    /**
     * @param  list<string>  $path
     * @param  array<string, mixed>  $payload
     */
    private function createPending(
        Organization $organization,
        Person $person,
        User $actor,
        RequestType $type,
        array $path,
        array $payload,
        ?string $note,
    ): WorkflowRequest {
        $workflow = Workflow::query()
            ->where('organization_id', $organization->id)
            ->where('type', $type->value)
            ->first();

        $request = DB::transaction(function () use ($organization, $person, $actor, $type, $path, $workflow, $payload, $note) {
            $request = WorkflowRequest::query()->create([
                'organization_id' => $organization->id,
                'workflow_id' => $workflow?->id,
                'person_id' => $person->id,
                'submitted_by_user_id' => $actor->id,
                'type' => $type,
                'status' => RequestStatus::Pending,
                'current_role' => $path[0],
                'step_index' => 0,
                'approval_path' => $path,
                'payload' => $payload,
            ]);

            $this->record($request, $actor, RequestActionType::Submit, $note);

            return $request;
        });

        $this->notifyApprovers($request->load(['person', 'organization']));

        return $request;
    }

    public function approve(WorkflowRequest $request, User $actor, ?string $comment = null): WorkflowRequest
    {
        $this->assertPending($request);
        $this->assertCanAct($request, $actor);

        $path = $request->approval_path ?? [];
        $nextIndex = $request->step_index + 1;

        $result = DB::transaction(function () use ($request, $actor, $comment, $path, $nextIndex) {
            $this->record($request, $actor, RequestActionType::Approve, $comment);

            if ($nextIndex >= count($path)) {
                $this->finalizeApproved($request, $actor);

                return $request->refresh();
            }

            $request->update([
                'step_index' => $nextIndex,
                'current_role' => $path[$nextIndex],
            ]);

            $this->notifyApprovers($request->fresh(['person', 'organization']));

            return $request->fresh();
        });

        $this->audit->record(
            $result->organization ?? $request->organization,
            AuditAction::RequestApprove,
            $actor,
            'Odobreno: '.$result->type->label().($comment ? ' · '.$comment : ''),
            $result->person,
            WorkflowRequest::class,
            $result->id,
            ['status' => $result->status->value],
        );

        return $result;
    }

    public function reject(WorkflowRequest $request, User $actor, string $reason): WorkflowRequest
    {
        $this->assertPending($request);
        $this->assertCanAct($request, $actor);

        if (blank($reason)) {
            throw ValidationException::withMessages([
                'comment' => 'Odbijanje zahtijeva razlog.',
            ]);
        }

        $result = DB::transaction(function () use ($request, $actor, $reason) {
            $this->record($request, $actor, RequestActionType::Reject, $reason);
            $request->update([
                'status' => RequestStatus::Rejected,
                'current_role' => null,
                'decided_at' => now(),
            ]);

            $request->submittedBy?->notify(new WorkflowRequestNotification($request->fresh(['person', 'organization']), 'rejected'));

            return $request->fresh();
        });

        $this->audit->record(
            $result->organization ?? $request->organization,
            AuditAction::RequestReject,
            $actor,
            'Odbijeno: '.$result->type->label().' · '.$reason,
            $result->person,
            WorkflowRequest::class,
            $result->id,
        );

        return $result;
    }

    public function cancel(WorkflowRequest $request, User $actor): WorkflowRequest
    {
        $this->assertPending($request);

        if ((int) $request->submitted_by_user_id !== (int) $actor->id
            && ! $this->rbac->can($request->organization_id, $actor->id, 'requests.approve')) {
            abort(403, 'Nemate ovlasti za ovu radnju.');
        }

        return DB::transaction(function () use ($request, $actor) {
            $this->record($request, $actor, RequestActionType::Cancel, null);
            $request->update([
                'status' => RequestStatus::Cancelled,
                'current_role' => null,
                'decided_at' => now(),
            ]);

            return $request->fresh();
        });
    }

    public function canAct(WorkflowRequest $request, User $actor): bool
    {
        if (! $request->isPending()) {
            return false;
        }

        $orgId = $request->organization_id;
        $userId = (int) $actor->id;

        if ($this->rbac->can($orgId, $userId, 'people.access') || $this->rbac->isOwner($orgId, $userId)) {
            return true;
        }

        if ($request->current_role === OrganizationRole::Manager->value
            && (int) $request->person?->manager_user_id === $userId) {
            return true;
        }

        if ($request->current_role === OrganizationRole::Hr->value
            && $this->rbac->roleForUser($orgId, $userId) === OrganizationRole::Hr) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function approvalPath(Person $person, RequestType $type, int $days): array
    {
        $workflow = Workflow::query()
            ->where('organization_id', $person->organization_id)
            ->where('type', $type->value)
            ->with('steps')
            ->first();

        if ($workflow && $workflow->steps->isNotEmpty()) {
            $path = [];
            foreach ($workflow->steps as $step) {
                if ($step->matches($days, (bool) $person->manager_user_id)) {
                    $path[] = $step->role;
                }
            }
            if ($path !== []) {
                return $path;
            }

            return [OrganizationRole::Hr->value];
        }

        $managerFirst = $person->manager_user_id && (
            ($type === RequestType::LeaveAnnual && $days <= 3)
            || $type === RequestType::Overtime
            || $type === RequestType::PunchCorrection
            || $type === RequestType::ShiftSwap
        );

        if ($managerFirst) {
            return [OrganizationRole::Manager->value];
        }

        return [OrganizationRole::Hr->value];
    }

    private function finalizeApproved(WorkflowRequest $request, User $actor): void
    {
        $person = $request->person()->firstOrFail();

        match ($request->type) {
            RequestType::Overtime => $this->applyOvertime($person, $request),
            RequestType::PunchCorrection => $this->applyPunchCorrection($person, $actor, $request),
            RequestType::PersonalDataChange => $this->applyPersonalData($person, $request),
            RequestType::ShiftSwap => $this->applyShiftSwap($person, $request),
            default => $this->applyLeave($person, $request),
        };

        $payload = $request->payload;
        $payload['decision'] = [
            'issued_at' => now()->toIso8601String(),
            'approver_name' => $actor->name,
            'approver_user_id' => $actor->id,
        ];

        if ($request->type === RequestType::LeaveAnnual) {
            $year = (int) Carbon::parse($request->fromDate())->year;
            $snapshot = $this->leave->snapshot($person->fresh(), $year);
            $payload['decision']['number'] = sprintf('GO-%d/%d', $request->id, $year);
            $payload['decision']['year'] = $snapshot['year'];
            $payload['decision']['remaining'] = $snapshot['remaining'];
            $payload['decision']['remaining_old'] = $snapshot['remaining_old'];
            $payload['decision']['remaining_new'] = $snapshot['remaining_new'];
        }

        $request->update([
            'status' => RequestStatus::Approved,
            'current_role' => null,
            'decided_at' => now(),
            'payload' => $payload,
        ]);

        if ($request->type === RequestType::LeaveAnnual) {
            $this->documents->storeLeaveDecision($request->fresh(['person', 'organization']), $person);
        }

        $request->submittedBy?->notify(new WorkflowRequestNotification($request->fresh(['person', 'organization']), 'approved'));
    }

    private function applyShiftSwap(Person $person, WorkflowRequest $request): void
    {
        $day = Carbon::parse($request->fromDate())->timezone(config('app.timezone'))->startOfDay();
        $counterpart = Person::query()
            ->where('organization_id', $person->organization_id)
            ->where('id', (int) ($request->payload['counterpart_id'] ?? 0))
            ->first();

        if ($counterpart === null) {
            return;
        }

        $this->locks->assertWritable($person, $day);
        $this->locks->assertWritable($counterpart, $day);

        $ownShiftId = $request->payload['own_shift_id'] ?? $this->shifts->forPersonOn($person, $day)?->id;
        $theirShiftId = $request->payload['counterpart_shift_id'] ?? $this->shifts->forPersonOn($counterpart, $day)?->id;

        ShiftOverride::putForDay(
            (int) $person->organization_id,
            (int) $person->id,
            $day->toDateString(),
            $theirShiftId ? (int) $theirShiftId : null,
            (int) $request->id,
        );
        ShiftOverride::putForDay(
            (int) $person->organization_id,
            (int) $counterpart->id,
            $day->toDateString(),
            $ownShiftId ? (int) $ownShiftId : null,
            (int) $request->id,
        );
    }

    private function applyLeave(Person $person, WorkflowRequest $request): void
    {
        $dates = $request->payload['dates'] ?? WorkingDays::dates(
            Carbon::parse($request->fromDate()),
            Carbon::parse($request->toDate()),
        );
        $code = $request->absenceCode() ?? 'GO';
        $year = (int) Carbon::parse($request->fromDate())->year;

        $this->assertWritableDays($person, $dates);

        if ($request->type === RequestType::LeaveAnnual) {
            $this->leave->consume($person, $request->days(), $year);
        }

        foreach ($dates as $date) {
            $this->writeAbsenceDay($person, $date, $code);
        }
    }

    private function applyOvertime(Person $person, WorkflowRequest $request): void
    {
        $day = Carbon::parse($request->fromDate())->startOfDay();
        $minutes = (int) ($request->payload['minutes'] ?? 0);
        $this->locks->assertWritable($person, $day);

        $entry = TimeEntry::query()
            ->where('person_id', $person->id)
            ->whereDate('work_date', $day->toDateString())
            ->first();

        $approved = (int) ($entry?->approved_overtime_minutes ?? 0) + $minutes;

        if ($entry !== null) {
            $entry->approved_overtime_minutes = $approved;
            $entry->overtime_minutes = max((int) $entry->overtime_minutes, $approved);
            $entry->evidential_minutes = max((int) $entry->evidential_minutes, (int) $entry->total_minutes, $approved);
            $entry->save();

            return;
        }

        TimeEntry::query()->create([
            'organization_id' => $person->organization_id,
            'person_id' => $person->id,
            'work_date' => $day->toDateString(),
            'approved_overtime_minutes' => $approved,
            'overtime_minutes' => $approved,
            'evidential_minutes' => $approved,
            'status' => TimeEntryStatus::Complete,
        ]);
    }

    private function applyPunchCorrection(Person $person, User $actor, WorkflowRequest $request): void
    {
        $punch = Punch::query()
            ->where('person_id', $person->id)
            ->where('id', (int) ($request->payload['punch_id'] ?? 0))
            ->first();

        if ($punch === null) {
            throw ValidationException::withMessages([
                'punch_id' => 'Izvorna prijava više ne postoji.',
            ]);
        }

        $this->clock->correct(
            $person,
            $actor,
            $punch,
            Carbon::parse($request->payload['occurred_at']),
            (string) ($request->payload['note'] ?? 'Ispravak prijave'),
        );
    }

    private function applyPersonalData(Person $person, WorkflowRequest $request): void
    {
        $updates = [];

        foreach ($request->payload['changes'] ?? [] as $field => $change) {
            if (! array_key_exists($field, PersonalDataChange::FIELDS)) {
                continue;
            }

            $updates[$field] = $change['to'] ?? null;
        }

        if ($updates === []) {
            throw ValidationException::withMessages([
                'type' => 'Zahtjev nema podataka za primjenu.',
            ]);
        }

        $person->update($updates);
    }

    /**
     * @param  list<string>  $dates
     */
    private function writeAbsenceDay(Person $person, string $date, string $code): void
    {
        $existing = TimeEntry::query()
            ->where('person_id', $person->id)
            ->whereDate('work_date', $date)
            ->first();

        $payload = [
            'organization_id' => $person->organization_id,
            'person_id' => $person->id,
            'work_date' => $date,
            'started_at' => null,
            'ended_at' => null,
            'break_minutes' => 0,
            'total_minutes' => 0,
            'night_minutes' => 0,
            'overtime_minutes' => 0,
            'sunday_minutes' => 0,
            'holiday_minutes' => 0,
            'evidential_minutes' => 480,
            'absence_code' => $code,
            'absence_minutes' => 480,
            'status' => TimeEntryStatus::Complete,
            'exception_code' => null,
        ];

        if ($existing !== null) {
            $existing->fill($payload)->save();

            return;
        }

        TimeEntry::query()->create($payload);
    }

    /**
     * @param  list<string>  $dates
     */
    private function assertWritableDays(Person $person, array $dates): void
    {
        foreach ($dates as $date) {
            try {
                $this->locks->assertWritable($person, $date);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages([
                    'to' => collect($e->errors())->flatten()->first()
                        ?: 'Dan '.$date.' je zaključan i ne može se upisati odsutnost.',
                ]);
            }

            $existing = TimeEntry::query()
                ->where('person_id', $person->id)
                ->whereDate('work_date', $date)
                ->first();

            if ($existing && $existing->started_at) {
                throw ValidationException::withMessages([
                    'to' => 'Dan '.$date.' već ima evidentirani rad. Prvo riješite slog.',
                ]);
            }
        }
    }

    /**
     * @param  list<string>  $dates
     */
    private function assertNoOverlap(Person $person, array $dates, ?string $code): void
    {
        $open = WorkflowRequest::query()
            ->where('person_id', $person->id)
            ->whereIn('status', [RequestStatus::Pending->value, RequestStatus::Approved->value])
            ->whereIn('type', [RequestType::LeaveAnnual->value, RequestType::LeaveOther->value])
            ->get();

        foreach ($open as $existing) {
            $existingDates = $existing->payload['dates'] ?? [];
            if (array_intersect($dates, $existingDates) !== []) {
                throw ValidationException::withMessages([
                    'from' => 'U tom razdoblju već postoji zahtjev ili odobrena odsutnost.',
                ]);
            }
        }

        $busy = TimeEntry::query()
            ->where('person_id', $person->id)
            ->where(function ($query) use ($dates) {
                foreach ($dates as $date) {
                    $query->orWhereDate('work_date', $date);
                }
            })
            ->where(function ($query) {
                $query->whereNotNull('absence_code')->orWhereNotNull('started_at');
            })
            ->exists();

        if ($busy) {
            throw ValidationException::withMessages([
                'from' => 'U šihterici već postoji slog za neki od odabranih dana.',
            ]);
        }
    }

    private function assertPending(WorkflowRequest $request): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Zahtjev više nije na odobrenju.',
            ]);
        }
    }

    private function assertCanAct(WorkflowRequest $request, User $actor): void
    {
        if (! $this->canAct($request, $actor)) {
            abort(403, 'Nemate ovlasti za ovu radnju.');
        }
    }

    private function record(WorkflowRequest $request, User $actor, RequestActionType $action, ?string $comment): void
    {
        WorkflowRequestAction::query()->create([
            'workflow_request_id' => $request->id,
            'user_id' => $actor->id,
            'action' => $action,
            'comment' => $comment,
        ]);
    }

    private function notifyApprovers(WorkflowRequest $request): void
    {
        $users = $this->approverUsers($request);

        foreach ($users as $user) {
            $user->notify(new WorkflowRequestNotification($request, 'waiting'));
        }
    }

    /**
     * @return list<User>
     */
    private function approverUsers(WorkflowRequest $request): array
    {
        if ($request->current_role === OrganizationRole::Manager->value && $request->person?->manager_user_id) {
            $manager = User::query()->find($request->person->manager_user_id);

            return $manager ? [$manager] : [];
        }

        return OrganizationUser::query()
            ->with('user')
            ->where('organization_id', $request->organization_id)
            ->whereIn('role', [OrganizationRole::Owner->value, OrganizationRole::Hr->value])
            ->get()
            ->map(fn (OrganizationUser $membership) => $membership->user)
            ->filter()
            ->unique('id')
            ->values()
            ->all();
    }
}
