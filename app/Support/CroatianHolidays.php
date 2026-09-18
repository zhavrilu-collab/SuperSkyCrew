<?php

namespace App\Support;

use Carbon\CarbonInterface;

class CroatianHolidays
{
    /** @var list<string> m-d */
    private const FIXED = [
        '01-01',
        '01-06',
        '05-01',
        '05-30',
        '06-22',
        '08-05',
        '08-15',
        '11-01',
        '11-18',
        '12-25',
        '12-26',
    ];

    public static function isHoliday(CarbonInterface $date): bool
    {
        return in_array($date->format('m-d'), self::FIXED, true);
    }

    public static function isWorkingDay(CarbonInterface $date): bool
    {
        return $date->isWeekday() && ! self::isHoliday($date);
    }
}
