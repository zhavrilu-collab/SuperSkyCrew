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
    case MockGps = 'mock_gps';
    case WeeklyHours = 'weekly_hours';
    case WeeklyRest = 'weekly_rest';

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
            self::MockGps => 'Sumnja na lažni GPS',
            self::WeeklyHours => 'Tjedni fond sati (ZOR)',
            self::WeeklyRest => 'Narušen tjedni odmor',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::MissingOut, self::Overlapping, self::OutWithoutIn, self::Geofence => 'text-bg-danger',
            self::DailyRest, self::Late, self::HolidayWork, self::SundayWork, self::WeeklyHours, self::WeeklyRest => 'text-bg-warning',
            self::MonthlyFund, self::MockGps => 'text-bg-info',
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
