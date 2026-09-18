<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class WorkingDays
{
    /**
     * @return list<string>
     */
    public static function dates(CarbonInterface $from, CarbonInterface $to): array
    {
        $dates = [];
        $cursor = Carbon::parse($from->toDateString(), $from->timezone)->startOfDay();
        $end = Carbon::parse($to->toDateString(), $to->timezone)->startOfDay();

        if ($end->lt($cursor)) {
            return [];
        }

        for ($day = $cursor->copy(); $day->lte($end); $day->addDay()) {
            if (CroatianHolidays::isWorkingDay($day)) {
                $dates[] = $day->toDateString();
            }
        }

        return $dates;
    }
}
