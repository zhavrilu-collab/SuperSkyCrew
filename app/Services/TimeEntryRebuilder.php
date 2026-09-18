<?php

namespace App\Services;

use App\Enums\ExceptionCode;
use App\Enums\PunchType;
use App\Enums\TimeEntryStatus;
use App\Models\Person;
use App\Models\Punch;
use App\Models\TimeEntry;
use App\Support\CroatianHolidays;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class TimeEntryRebuilder
{
    public function __construct(
        private readonly ShiftResolver $shifts,
    ) {}

    public function rebuild(Person $person, CarbonInterface $date): TimeEntry
    {
        $tz = config('app.timezone', 'Europe/Zagreb');
        $day = Carbon::parse($date->toDateString(), $tz)->startOfDay();

        $existing = TimeEntry::query()
            ->where('person_id', $person->id)
            ->whereDate('work_date', $day->toDateString())
            ->first();

        if ($existing?->isLocked()) {
            return $existing;
        }

        $punches = Punch::query()
            ->where('person_id', $person->id)
            ->whereBetween('occurred_at_device', [$day->copy(), $day->copy()->endOfDay()])
            ->whereDoesntHave('corrections')
            ->orderBy('occurred_at_device')
            ->orderBy('id')
            ->get();

        $openIn = null;
        $openBreak = null;
        $workSeconds = 0;
        $breakSeconds = 0;
        $nightSeconds = 0;
        $firstIn = null;
        $lastOut = null;
        $exception = null;

        foreach ($punches as $punch) {
            $at = $punch->occurred_at_device->clone()->timezone($tz);

            match ($punch->type) {
                PunchType::In => $this->handleIn($at, $openIn, $firstIn, $exception),
                PunchType::Out => $this->handleOut($at, $openIn, $workSeconds, $nightSeconds, $lastOut, $exception),
                PunchType::BreakStart => $openBreak = $at,
                PunchType::BreakEnd => $this->handleBreakEnd($at, $openBreak, $breakSeconds),
            };
        }

        $shift = $this->shifts->forPersonOn($person, $day);
        $plannedStart = $shift?->startsOn($day);
        $plannedEnd = $shift?->endsOn($day);
        $late = $this->lateException($firstIn, $plannedStart);

        if ($openIn !== null) {
            if ($late !== null && ! $day->isBefore(Carbon::now($tz)->startOfDay())) {
                $exception ??= $late;
            }
            $exception ??= ExceptionCode::MissingOut->value;
        }

        $netSeconds = max(0, $workSeconds - $breakSeconds);
        $totalMinutes = (int) round($netSeconds / 60);
        $nightMinutes = (int) round($nightSeconds / 60);
        $breakMinutes = (int) round($breakSeconds / 60);
        $overtime = max(0, $totalMinutes - 480);
        $approvedOvertime = (int) ($existing?->approved_overtime_minutes ?? 0);
        $overtime = max($overtime, $approvedOvertime);
        $sunday = $day->isSunday() ? $totalMinutes : 0;
        $holiday = CroatianHolidays::isHoliday($day) ? $totalMinutes : 0;
        $manual = (bool) ($existing?->evidential_manual);
        $defaultEvidential = (int) ($existing?->absence_minutes ?: $totalMinutes);
        $defaultCode = $existing?->absence_code ?: ($totalMinutes > 0 ? 'RD' : null);

        $exception ??= $this->dailyRestException($person, $firstIn);
        $exception ??= $this->monthlyFundException($person, $day, $totalMinutes, $existing?->id);
        $exception ??= $late;

        $status = TimeEntryStatus::Draft;
        if ($firstIn && $lastOut && $openIn === null) {
            $status = TimeEntryStatus::Complete;
        } elseif ($exception === ExceptionCode::MissingOut->value && $day->isBefore(Carbon::now($tz)->startOfDay())) {
            $status = TimeEntryStatus::Complete;
        }

        $payload = [
            'organization_id' => $person->organization_id,
            'person_id' => $person->id,
            'planned_shift_id' => $shift?->id,
            'planned_start' => $plannedStart,
            'planned_end' => $plannedEnd,
            'work_date' => $day->toDateString(),
            'started_at' => $firstIn,
            'ended_at' => $lastOut,
            'break_minutes' => $breakMinutes,
            'downtime_minutes' => 0,
            'total_minutes' => $totalMinutes,
            'field_work_minutes' => 0,
            'standby_minutes' => 0,
            'night_minutes' => $nightMinutes,
            'overtime_minutes' => $overtime,
            'approved_overtime_minutes' => $approvedOvertime,
            'sunday_minutes' => $sunday,
            'holiday_minutes' => $holiday,
            'evidential_minutes' => $manual ? (int) $existing->evidential_minutes : $defaultEvidential,
            'evidential_manual' => $manual,
            'evidential_code' => $manual ? $existing->evidential_code : $defaultCode,
            'evidential_note' => $manual ? $existing->evidential_note : null,
            'evidential_cost_center_id' => $manual
                ? $existing->evidential_cost_center_id
                : ($existing?->evidential_cost_center_id ?: $person->cost_center_id),
            'absence_code' => $existing?->absence_code,
            'absence_minutes' => $existing?->absence_minutes ?? 0,
            'status' => $status,
            'exception_code' => $exception,
        ];

        if ($existing !== null && $existing->exception_code !== $exception) {
            $payload['exception_resolved_at'] = null;
            $payload['exception_resolved_by'] = null;
            $payload['exception_note'] = null;
        }

        if ($existing !== null) {
            $existing->fill($payload)->save();

            return $existing;
        }

        return TimeEntry::query()->create($payload);
    }

    private function handleIn(Carbon $at, ?Carbon &$openIn, ?Carbon &$firstIn, ?string &$exception): void
    {
        if ($openIn !== null) {
            $exception ??= ExceptionCode::Overlapping->value;
        }

        $openIn = $at;
        $firstIn ??= $at;
    }

    private function handleOut(
        Carbon $at,
        ?Carbon &$openIn,
        int &$workSeconds,
        int &$nightSeconds,
        ?Carbon &$lastOut,
        ?string &$exception,
    ): void {
        if ($openIn === null) {
            $exception ??= ExceptionCode::OutWithoutIn->value;

            return;
        }

        $workSeconds += (int) abs($openIn->diffInSeconds($at));
        $nightSeconds += $this->nightSeconds($openIn, $at);
        $lastOut = $at;
        $openIn = null;
    }

    private function handleBreakEnd(Carbon $at, ?Carbon &$openBreak, int &$breakSeconds): void
    {
        if ($openBreak === null) {
            return;
        }

        $breakSeconds += (int) abs($openBreak->diffInSeconds($at));
        $openBreak = null;
    }

    private function nightSeconds(Carbon $from, Carbon $to): int
    {
        $total = 0;
        $cursor = $from->copy();

        while ($cursor < $to) {
            $next = $cursor->copy()->startOfHour()->addHour();
            if ($next > $to) {
                $next = $to->copy();
            }

            $hour = (int) $cursor->format('G');
            if ($hour >= 22 || $hour < 6) {
                $total += (int) abs($cursor->diffInSeconds($next));
            }

            $cursor = $next;
        }

        return $total;
    }

    private function dailyRestException(Person $person, ?Carbon $firstIn): ?string
    {
        if ($firstIn === null) {
            return null;
        }

        $previousOut = Punch::query()
            ->where('person_id', $person->id)
            ->where('type', PunchType::Out)
            ->where('occurred_at_device', '<', $firstIn)
            ->whereDoesntHave('corrections')
            ->orderByDesc('occurred_at_device')
            ->orderByDesc('id')
            ->first();

        if ($previousOut === null) {
            return null;
        }

        $restMinutes = (int) round($previousOut->occurred_at_device->diffInMinutes($firstIn, false));

        return $restMinutes < 12 * 60 ? ExceptionCode::DailyRest->value : null;
    }

    private function monthlyFundException(Person $person, Carbon $day, int $todayMinutes, ?int $existingId): ?string
    {
        if ($todayMinutes <= 0) {
            return null;
        }

        $monthStart = $day->copy()->startOfMonth();
        $expectedDays = count(WorkingDays::dates($monthStart, $day));
        if ($expectedDays === 0) {
            return null;
        }

        $prior = (int) TimeEntry::query()
            ->where('person_id', $person->id)
            ->whereDate('work_date', '>=', $monthStart->toDateString())
            ->whereDate('work_date', '<', $day->toDateString())
            ->when($existingId, fn ($query) => $query->where('id', '!=', $existingId))
            ->sum('total_minutes');

        $expected = $expectedDays * 480;

        return ($prior + $todayMinutes) > $expected ? ExceptionCode::MonthlyFund->value : null;
    }

    private function lateException(?Carbon $firstIn, ?Carbon $plannedStart): ?string
    {
        if ($firstIn === null || $plannedStart === null) {
            return null;
        }

        $limit = $plannedStart->copy()->addMinutes(ShiftResolver::LATE_GRACE_MINUTES);

        return $firstIn->gt($limit) ? ExceptionCode::Late->value : null;
    }
}
