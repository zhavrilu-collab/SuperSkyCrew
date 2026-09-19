<?php

namespace App\Http\Controllers;

use App\Enums\ClockChannel;
use App\Enums\PunchType;
use App\Models\AbsenceCode;
use App\Models\ComplianceExport;
use App\Models\DocumentHandover;
use App\Models\Person;
use App\Models\Punch;
use App\Models\TimeEntry;
use App\Services\ClockService;
use App\Services\DepartmentScopeService;
use App\Services\OrganizationRbacService;
use App\Services\PeriodLockService;
use App\Services\ShiftResolver;
use App\Services\TimeEntryRebuilder;
use App\Services\AuditService;
use App\Enums\AuditAction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimesheetController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly ClockService $clock,
        private readonly PeriodLockService $locks,
        private readonly DepartmentScopeService $scope,
        private readonly ShiftResolver $shifts,
        private readonly TimeEntryRebuilder $rebuilder,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->authorizeTime($organization->id);

        [$from, $to] = $this->range($request);
        $userId = (int) Auth::id();

        $peopleQuery = Person::query()
            ->forOrganization($organization)
            ->clockEligible()
            ->orderBy('last_name');
        $this->scope->restrictPeopleQuery($peopleQuery, $organization, $userId);
        $people = $peopleQuery->get();

        $entries = TimeEntry::query()
            ->forOrganization($organization)
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->get()
            ->groupBy('person_id');

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $days[] = $d->copy();
        }

        $presentIds = Punch::query()
            ->forOrganization($organization)
            ->whereDoesntHave('corrections')
            ->whereIn('person_id', $people->pluck('id'))
            ->orderByDesc('occurred_at_device')
            ->get()
            ->unique('person_id')
            ->filter(fn (Punch $punch) => in_array($punch->type, [PunchType::In, PunchType::BreakStart, PunchType::BreakEnd], true))
            ->pluck('person_id');

        $lock = $this->locks->forMonth($organization, (int) $from->year, (int) $from->month);
        $lock?->load('lockedBy');

        return view('organization.timesheet.index', [
            'organization' => $organization,
            'people' => $people,
            'entries' => $entries,
            'days' => $days,
            'from' => $from,
            'to' => $to,
            'presentIds' => $presentIds,
            'canManual' => $this->rbac->can($organization->id, $userId, 'time.access'),
            'canLock' => $this->rbac->can($organization->id, $userId, 'time.lock'),
            'canInspect' => $this->rbac->can($organization->id, $userId, 'inspection.export'),
            'canPayroll' => $this->rbac->can($organization->id, $userId, 'payroll.export'),
            'periodLock' => $lock,
            'plan' => $this->shifts->mapForPeople($people, $from, $to),
            'exceptionCount' => TimeEntry::query()
                ->forOrganization($organization)
                ->openExceptions()
                ->whereIn('person_id', $people->pluck('id'))
                ->whereDate('work_date', '>=', $from->toDateString())
                ->whereDate('work_date', '<=', $to->toDateString())
                ->count(),
            'absenceCodes' => AbsenceCode::query()->forOrganization($organization)->orderBy('kind')->orderBy('code')->get(),
        ]);
    }

    public function show(string $slug, Person $person, string $date): View
    {
        $organization = app('currentOrganization');
        abort_unless($person->organization_id === $organization->id, 404);
        $this->authorizeTimeOrSelf($organization->id, $person);
        abort_unless($this->scope->canManagePerson($person, (int) Auth::id()), 403, 'Nemate ovlasti za ovu radnju.');

        $day = Carbon::parse($date, config('app.timezone'))->startOfDay();
        $entry = TimeEntry::query()
            ->where('person_id', $person->id)
            ->whereDate('work_date', $day->toDateString())
            ->with('evidentialCostCenter')
            ->first();

        $punches = Punch::query()
            ->where('person_id', $person->id)
            ->whereBetween('occurred_at_device', [$day->copy(), $day->copy()->endOfDay()])
            ->with('corrections')
            ->orderBy('occurred_at_device')
            ->get();

        $locked = $this->locks->isLocked($organization->id, $day) || ($entry?->isLocked() ?? false);
        $userId = (int) Auth::id();
        $canOpenTimesheet = $this->rbac->can($organization->id, $userId, 'time.access')
            || $this->rbac->can($organization->id, $userId, 'payroll.export');
        $canEditEvidential = $this->rbac->can($organization->id, $userId, 'time.lock') && ! $locked;
        $person->loadMissing('costCenter');
        $costCenters = $this->scope->costCentersOn($organization, $day);
        if ($entry?->evidentialCostCenter && ! $costCenters->contains('id', $entry->evidential_cost_center_id)) {
            $costCenters->push($entry->evidentialCostCenter);
        }
        if ($person->costCenter && ! $costCenters->contains('id', $person->cost_center_id)) {
            $costCenters->push($person->costCenter);
        }

        return view('organization.timesheet.day', [
            'organization' => $organization,
            'person' => $person,
            'day' => $day,
            'entry' => $entry,
            'punches' => $punches,
            'canManual' => $this->rbac->can($organization->id, $userId, 'time.access') && ! $locked,
            'canResolve' => $this->rbac->can($organization->id, $userId, 'time.access') && ($entry?->hasOpenException() ?? false),
            'periodLocked' => $locked,
            'canOpenTimesheet' => $canOpenTimesheet,
            'canEditEvidential' => $canEditEvidential,
            'plannedShift' => $this->shifts->forPersonOn($person, $day),
            'codes' => AbsenceCode::query()->forOrganization($organization)->orderBy('kind')->orderBy('code')->get(),
            'costCenters' => $costCenters,
        ]);
    }

    public function punchPhoto(string $slug, Person $person, Punch $punch): StreamedResponse
    {
        $organization = app('currentOrganization');
        abort_unless($person->organization_id === $organization->id, 404);
        abort_unless($punch->person_id === $person->id, 404);
        abort_unless($punch->organization_id === $organization->id, 404);
        $this->authorizeTimeOrSelf($organization->id, $person);
        abort_unless($this->scope->canManagePerson($person, (int) Auth::id()), 403, 'Nemate ovlasti za ovu radnju.');
        abort_unless($punch->hasPhoto() && Storage::disk('local')->exists($punch->photo_path), 404);

        return Storage::disk('local')->response($punch->photo_path, 'prijava.jpg', [
            'Content-Type' => 'image/jpeg',
        ]);
    }

    public function mine(Request $request): View|RedirectResponse
    {
        $organization = app('currentOrganization');
        $person = Person::query()
            ->forOrganization($organization)
            ->where('user_id', Auth::id())
            ->first();

        if ($person === null) {
            return redirect()
                ->route('organization.dashboard', $organization->slug)
                ->withErrors(['clock' => 'Nemate povezanu karticu radnika.']);
        }

        $from = Carbon::parse($request->input('from', now()->toDateString()))
            ->timezone(config('app.timezone'))
            ->startOfWeek(Carbon::MONDAY);
        $to = $from->copy()->endOfWeek(Carbon::SUNDAY);
        $entries = TimeEntry::query()
            ->where('person_id', $person->id)
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->get()
            ->keyBy(fn (TimeEntry $entry) => $entry->work_date->toDateString());

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $days[] = $d->copy();
        }

        return view('organization.timesheet.mine', [
            'organization' => $organization,
            'person' => $person,
            'days' => $days,
            'entries' => $entries,
            'from' => $from,
            'to' => $to,
            'prev' => $from->copy()->subWeek(),
            'next' => $from->copy()->addWeek(),
            'plan' => $this->shifts->mapForPeople(collect([$person]), $from, $to)[$person->id] ?? [],
        ]);
    }

    public function storeManual(Request $request, string $slug, Person $person): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'time.access');
        abort_unless($person->organization_id === $organization->id, 404);
        abort_unless($this->scope->canManagePerson($person, (int) Auth::id()), 403, 'Nemate ovlasti za ovu radnju.');

        $data = $request->validate([
            'occurred_at' => ['required', 'date'],
            'type' => ['required', 'in:in,out,break_start,break_end'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->clock->punch($person, $request->user(), [
            'type' => $data['type'],
            'channel' => ClockChannel::Manager->value,
            'occurred_at' => $data['occurred_at'],
            'reason' => $data['reason'],
        ]);

        return back()->with('status', 'Ručni unos je zabilježen.');
    }

    public function storeEvidential(Request $request, string $slug, Person $person, string $date): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'time.lock');
        abort_unless($person->organization_id === $organization->id, 404);

        $day = Carbon::parse($date, config('app.timezone'))->startOfDay();
        abort_if($this->locks->isLocked($organization->id, $day), 403, 'Razdoblje je zaključano.');

        $entry = $this->rebuilder->rebuild($person, $day);
        abort_if($entry->isLocked(), 403, 'Slog je zaključan.');

        if ($request->boolean('revert')) {
            $entry->evidential_manual = false;
            $entry->evidential_note = null;
            $entry->save();
            $this->rebuilder->rebuild($person, $day);

            $this->audit->record(
                $organization,
                AuditAction::EvidentialCorrect,
                $request->user(),
                'Evidencijski sati vraćeni na realizaciju · '.$person->fullName().' '.$day->format('d.m.Y.'),
                $person,
                TimeEntry::class,
                $entry->id,
                ['revert' => true, 'date' => $day->toDateString()],
            );

            return back()->with('status', 'Evidencijski sati vraćeni na realizaciju.');
        }

        $data = $request->validate([
            'evidential_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'evidential_code' => [
                'required',
                'string',
                'max:16',
                Rule::exists('absence_codes', 'code')->where('organization_id', $organization->id),
            ],
            'evidential_cost_center_id' => [
                'nullable',
                Rule::exists('cost_centers', 'id')->where('organization_id', $organization->id),
            ],
            'evidential_note' => ['required', 'string', 'max:255'],
        ]);

        foreach (['evidential_cost_center_id'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        $entry->fill([
            'evidential_minutes' => $data['evidential_minutes'],
            'evidential_code' => $data['evidential_code'],
            'evidential_cost_center_id' => ($data['evidential_cost_center_id'] ?? null) ?: $person->cost_center_id,
            'evidential_note' => $data['evidential_note'],
            'evidential_manual' => true,
        ])->save();

        $this->audit->record(
            $organization,
            AuditAction::EvidentialCorrect,
            $request->user(),
            'Evidencijski sati: '.$data['evidential_code'].' '
                .round(((int) $data['evidential_minutes']) / 60, 2).' h · '.$person->fullName().' '.$day->format('d.m.Y.'),
            $person,
            TimeEntry::class,
            $entry->id,
            [
                'code' => $data['evidential_code'],
                'minutes' => (int) $data['evidential_minutes'],
                'note' => $data['evidential_note'],
            ],
        );

        return back()->with('status', 'Evidencijski sati su spremljeni.');
    }

    public function lock(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'time.lock');

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $lock = $this->locks->lock($organization, (int) $data['year'], (int) $data['month'], $request->user());

        return back()->with('status', 'Razdoblje '.$lock->label().' je zaključano.');
    }

    public function inspection(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'inspection.export');
        [$from, $to] = $this->range($request);

        $this->recordExport($organization->id, 'inspection', $from, $to);

        return view('organization.timesheet.inspection', [
            'organization' => $organization,
            'from' => $from,
            'to' => $to,
            'rows' => $this->reportRows($organization->id, $from, $to),
            'codes' => AbsenceCode::query()->forOrganization($organization)->orderBy('code')->get(),
            'handovers' => DocumentHandover::query()
                ->forOrganization($organization)
                ->with('person')
                ->whereDate('handed_on', '>=', $from->toDateString())
                ->whereDate('handed_on', '<=', $to->toDateString())
                ->orderBy('handed_on')
                ->get(),
            'exporter' => $request->user(),
            'exportedAt' => now(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'payroll.export');
        [$from, $to] = $this->range($request);
        $rows = $this->reportRows($organization->id, $from, $to);
        $this->recordExport($organization->id, 'payroll', $from, $to);

        $filename = 'evidencija-'.$from->toDateString().'-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Osoba', 'Datum', 'Početak', 'Završetak', 'Realizirano min', 'Pauza min',
                'Noć min', 'Prekovremeni min', 'Nedjelja min', 'Blagdan min',
                'Odsutnost', 'Odsutnost min', 'Šifra', 'Evidencijski min', 'Mjesto troška', 'Status', 'Iznimka',
            ], ';');
            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->startOfDay();
        if ($to->lt($from)) {
            $to = $from->copy();
        }

        return [$from, $to];
    }

    /**
     * @return list<list<string|int|null>>
     */
    private function reportRows(int $organizationId, Carbon $from, Carbon $to): array
    {
        $entries = TimeEntry::query()
            ->with(['person.costCenter', 'evidentialCostCenter'])
            ->where('organization_id', $organizationId)
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->orderBy('person_id')
            ->orderBy('work_date')
            ->get();

        $tz = config('app.timezone');
        $rows = [];
        foreach ($entries as $entry) {
            $costCenter = $entry->evidentialCostCenter ?: $entry->person?->costCenter;
            $rows[] = [
                $entry->person?->fullName() ?? '',
                $entry->work_date->toDateString(),
                $entry->started_at?->timezone($tz)->format('H:i') ?? '',
                $entry->ended_at?->timezone($tz)->format('H:i') ?? '',
                $entry->total_minutes,
                $entry->break_minutes,
                $entry->night_minutes,
                $entry->overtime_minutes,
                $entry->sunday_minutes,
                $entry->holiday_minutes,
                $entry->absence_code,
                $entry->absence_minutes,
                $entry->evidential_code,
                $entry->evidential_minutes,
                $costCenter?->summary() ?? '',
                $entry->status->label(),
                $entry->exception_code,
            ];
        }

        return $rows;
    }

    private function recordExport(int $organizationId, string $kind, Carbon $from, Carbon $to): void
    {
        ComplianceExport::query()->create([
            'organization_id' => $organizationId,
            'user_id' => Auth::id(),
            'kind' => $kind,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
        ]);

        $organization = app('currentOrganization');
        $action = $kind === 'inspection' ? AuditAction::InspectionExport : AuditAction::TimesheetExport;
        $this->audit->record(
            $organization,
            $action,
            Auth::user(),
            ($action === AuditAction::InspectionExport ? 'Inspekcijski paket' : 'Izvoz šihterice')
                .' '.$from->format('d.m.Y.').' – '.$to->format('d.m.Y.'),
            null,
            ComplianceExport::class,
            null,
            ['kind' => $kind, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
        );
    }

    private function authorizeTime(int $organizationId): void
    {
        $userId = (int) Auth::id();
        if (
            $this->rbac->can($organizationId, $userId, 'time.access')
            || $this->rbac->can($organizationId, $userId, 'payroll.export')
        ) {
            return;
        }

        abort(403, 'Nemate ovlasti za ovu radnju.');
    }

    private function authorizeTimeOrSelf(int $organizationId, Person $person): void
    {
        if ((int) $person->user_id === (int) Auth::id()) {
            return;
        }

        $this->authorizeTime($organizationId);
    }
}
