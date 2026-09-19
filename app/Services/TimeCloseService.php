<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Person;
use App\Models\Punch;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class TimeCloseService
{
    public function __construct(
        private readonly TimeEntryRebuilder $rebuilder,
        private readonly PeriodLockService $locks,
    ) {}

    public function closeYesterday(Organization $organization, ?CarbonInterface $now = null): int
    {
        $today = Carbon::parse(($now ?? now())->toDateString(), config('app.timezone'))->startOfDay();

        return $this->closeDay($organization, $today->copy()->subDay());
    }

    public function closeDay(Organization $organization, CarbonInterface $day): int
    {
        $date = Carbon::parse($day->toDateString(), config('app.timezone'))->startOfDay();
        $today = now(config('app.timezone'))->startOfDay();
        if (! $date->lt($today) || $this->locks->isLocked($organization->id, $date)) {
            return 0;
        }

        $personIds = Punch::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('occurred_at_device', [$date->copy(), $date->copy()->endOfDay()])
            ->distinct()
            ->pluck('person_id');

        $count = 0;
        Person::query()->whereIn('id', $personIds)->each(function (Person $person) use ($date, &$count) {
            $this->rebuilder->rebuild($person, $date);
            $count++;
        });

        return $count;
    }
}
