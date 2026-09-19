<?php

namespace App\Enums;

enum ExceptionCode: string
{
    case MissingOut = 'missing_out';
    case Overlapping = 'overlapping';
    case OutWithoutIn = 'out_without_in';
    case DailyRest = 'daily_rest';
    case MonthlyFund = 'monthly_fund';
    case Late = 'late';
    case Geofence = 'geofence';
    case HolidayWork = 'holiday_work';
    case SundayWork = 'sunday_work';

    public function label(): string
    {
        return match ($this) {
            self::MissingOut => 'Nedostaje odjava',
            self::Overlapping => 'Preklapanje prijava',
            self::OutWithoutIn => 'Odjava bez prijave',
            self::DailyRest => 'Narušen dnevni odmor (12 h)',
            self::MonthlyFund => 'Odstupanje od mjesečnog fonda',
            self::Late => 'Kašnjenje vs plan',
            self::Geofence => 'Prijava izvan zone',
            self::HolidayWork => 'Rad na blagdan',
            self::SundayWork => 'Rad u nedjelju',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::MissingOut, self::Overlapping, self::OutWithoutIn, self::Geofence => 'text-bg-danger',
            self::DailyRest, self::Late, self::HolidayWork, self::SundayWork => 'text-bg-warning',
            self::MonthlyFund => 'text-bg-info',
        };
    }

    public static function tryLabel(?string $code): string
    {
        if ($code === null || $code === '') {
            return '—';
        }

        return self::tryFrom($code)?->label() ?? $code;
    }
}
