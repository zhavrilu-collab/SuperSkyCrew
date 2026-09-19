<?php

namespace App\Services;

use App\Enums\ExceptionCode;
use App\Models\Person;
use App\Models\TimeEntry;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class WorkFundService
{
    public const DEFAULT_WEEKLY_HOURS = 40;

    public function weeklyHours(Person $person): int
    {
        $person->loadMissing('employmentContracts');
        $hours = (int) ($person->currentContract()?->weekly_hours ?: 0);

        return $hours > 0 ? $hours : self::DEFAULT_WEEKLY_HOURS;
    }

    public function dailyMinutes(Person $person): int
    {
        return (int) round($this->weeklyHours($person) * 60 / 5);
    }

    public function expectedMinutes(Person $person, CarbonInterface $from, CarbonInterface $to): int
    {
        return count(WorkingDays::dates($from, $to)) * $this->dailyMinutes($person);
    }

    /**
     * @param  Collection<int, Person>  $people
     * @return list<array<string, mixed>>
     */
    public function monthRows(Collection $people, CarbonInterface $month, ?CarbonInterface $asOf = null): array
    {
        $tz = config('app.timezone', 'Europe/Zagreb');
        $start = Carbon::parse($month->toDateString(), $tz)->startOfMonth();
        $end = $start->copy()->endOfMonth()->startOfDay();
        $cutoff = ($asOf ?? now()->timezone($tz))->copy()->startOfDay();
        if ($cutoff->gt($end)) {
            $cutoff = $end->copy();
        }
        if ($cutoff->lt($start)) {
            $cutoff = $start->copy();
        }

        $daysElapsed = count(WorkingDays::dates($start, $cutoff));
        $daysMonth = count(WorkingDays::dates($start, $end));
        $ids = $people->pluck('id');

        $grouped = TimeEntry::query()
            ->whereIn('person_id', $ids)
            ->whereDate('work_date', '>=', $start->toDateString())
            ->whereDate('work_date', '<=', $end->toDateString())
            ->get()
            ->groupBy('person_id');

        $flagged = TimeEntry::query()
            ->whereIn('person_id', $ids)
            ->whereDate('work_date', '>=', $start->toDateString())
            ->whereDate('work_date', '<=', $end->toDateString())
            ->where('exception_code', ExceptionCode::MonthlyFund->value)
            ->whereNull('exception_resolved_at')
            ->pluck('person_id')
            ->flip();

        $rows = [];
        foreach ($people as $person) {
            $daily = $this->dailyMinutes($person);
            $group = $grouped->get($person->id, collect());
            $total = (int) $group->sum('total_minutes');
            $evidential = (int) $group->sum('evidential_minutes');
            $expectedToDate = $daysElapsed * $daily;
            $counted = $evidential > 0 ? $evidential : $total;
            $delta = $counted - $expectedToDate;

            $rows[] = [
                'person' => $person,
                'name' => $person->fullName(),
                'weekly_hours' => $this->weeklyHours($person),
                'daily_minutes' => $daily,
                'days_elapsed' => $daysElapsed,
                'days_month' => $daysMonth,
                'expected_to_date' => $expectedToDate,
                'expected_month' => $daysMonth * $daily,
                'total_minutes' => $total,
                'evidential_minutes' => $evidential,
                'delta' => $delta,
                'over' => $delta > 0,
                'flagged' => $flagged->has($person->id),
            ];
        }

        return $rows;
    }
}
