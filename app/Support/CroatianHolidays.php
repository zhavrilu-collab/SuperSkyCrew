<?php

namespace App\Support;

use Carbon\Carbon;
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
        if (in_array($date->format('m-d'), self::FIXED, true)) {
            return true;
        }

        $year = (int) $date->year;
        $easter = self::easterSunday($year);

        return $date->isSameDay($easter)
            || $date->isSameDay($easter->copy()->addDay())
            || $date->isSameDay($easter->copy()->addDays(60));
    }

    public static function isWorkingDay(CarbonInterface $date): bool
    {
        return $date->isWeekday() && ! self::isHoliday($date);
    }

    /**
     * Uskrs (gregorijanski, Anonymous/Meeus).
     */
    public static function easterSunday(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day, 0, 0, 0, 'Europe/Zagreb');
    }
}
