<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\TimeEntryStatus;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Punch;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\CroatianHolidays;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PlanTransferService
{
    public function __construct(
        private readonly ShiftResolver $shifts,
        private readonly PeriodLockService $locks,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  Collection<int, Person>  $people
     */
    public function transfer(
        Organization $organization,
        Collection $people,
        CarbonInterface $from,
        CarbonInterface $to,
        User $actor,
    ): int {
        $count = 0;
        $start = Carbon::parse($from->toDateString(), config('app.timezone'))->startOfDay();
        $end = Carbon::parse($to->toDateString(), config('app.timezone'))->startOfDay();

        foreach ($people as $person) {
            for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                if ($this->applyDay($organization, $person, $day->copy())) {
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $this->audit->record(
                $organization,
                AuditAction::TimePlanTransfer,
                $actor,
                'Plan prenesen u šihtericu: '.$count.' slogova ('
                    .$start->format('d.m.').'–'.$end->format('d.m.Y.').')',
                null,
                TimeEntry::class,
                null,
                [
                    'from' => $start->toDateString(),
                    'to' => $end->toDateString(),
                    'count' => $count,
                ],
            );
        }

        return $count;
    }

    private function applyDay(Organization $organization, Person $person, Carbon $day): bool
    {
        if ($this->locks->isLocked($organization->id, $day)) {
            return false;
        }

        $shift = $this->shifts->forPersonOn($person, $day);
        if ($shift === null) {
            return false;
        }

        $net = $shift->netMinutesOn($day);
        if ($net <= 0) {
            return false;
        }

        if ($this->hasPunches($person, $day)) {
            return false;
        }

        $entry = TimeEntry::query()
            ->where('person_id', $person->id)
            ->whereDate('work_date', $day->toDateString())
            ->first();

        if ($entry?->isLocked() || $entry?->evidential_manual || $entry?->started_at) {
            return false;
        }

        if ($entry !== null && ($entry->absence_code || $entry->absence_minutes > 0)) {
            return false;
        }

        $plannedStart = $shift->startsOn($day);
        $plannedEnd = $shift->endsOn($day);
        $payload = [
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'work_date' => $day->toDateString(),
            'planned_shift_id' => $shift->id,
            'planned_start' => $plannedStart,
            'planned_end' => $plannedEnd,
            'break_minutes' => (int) $shift->break_minutes,
            'evidential_minutes' => $net,
            'evidential_code' => 'RD',
            'evidential_manual' => false,
            'evidential_cost_center_id' => $entry?->evidential_cost_center_id ?: $person->cost_center_id,
            'night_minutes' => $shift->is_night ? $net : 0,
            'shift_minutes' => $shift->is_shift ? $net : 0,
            'sunday_minutes' => $day->isSunday() ? $net : 0,
            'holiday_minutes' => CroatianHolidays::isHoliday($day) ? $net : 0,
            'status' => TimeEntryStatus::Complete,
        ];

        if ($entry !== null) {
            $entry->fill($payload)->save();
        } else {
            TimeEntry::query()->create($payload);
        }

        return true;
    }

    private function hasPunches(Person $person, Carbon $day): bool
    {
        return Punch::query()
            ->where('person_id', $person->id)
            ->whereBetween('occurred_at_device', [$day->copy(), $day->copy()->endOfDay()])
            ->whereDoesntHave('corrections')
            ->exists();
    }
}
