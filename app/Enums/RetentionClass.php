<?php

namespace App\Enums;

enum RetentionClass: string
{
    case WrittenReview = 'pisani_pregled';
    case Contract = 'uor_aneks';
    case Education = 'obrazovanje';
    case Payroll = 'place';
    case Safety = 'znr';
    case Pension = 'mirovina';
    case OtherFo = 'fo_clanak_10';
    case Other = 'ostalo';

    public function label(): string
    {
        return match ($this) {
            self::WrittenReview => 'Pisani pregled',
            self::Contract => 'UOR i aneksi',
            self::Education => 'Obrazovanje / dokazi',
            self::Payroll => 'Plaće, porez, doprinosi',
            self::Safety => 'ZNR / ozljede',
            self::Pension => 'Mirovina / staž s povećanjem',
            self::OtherFo => 'Druge FO (čl. 12.)',
            self::Other => 'Ostalo',
        };
    }

    public function period(): string
    {
        return match ($this) {
            self::WrittenReview => 'do isteka godine prestanka RO',
            self::Contract => '6 godina od isteka godine prestanka',
            self::Education => '6 godina od prestanka',
            self::Payroll => 'posebni propisi (obračun trajno / 11 god.)',
            self::Safety => 'posebni propisi',
            self::Pension => '40 godina od prestanka',
            self::OtherFo => '6 godina od prestanka rada',
            self::Other => '6 godina od nastanka',
        };
    }

    /**
     * Zadnji dan obveznog čuvanja. Null = rok još ne teče (nema prestanka).
     */
    public function retainUntil(?\Carbon\CarbonInterface $employmentEnded, \Carbon\CarbonInterface $origin): ?\Carbon\CarbonInterface
    {
        $origin = $origin->copy()->startOfDay();
        $ended = $employmentEnded?->copy()->startOfDay();

        return match ($this) {
            self::WrittenReview => $ended?->copy()->endOfYear()->startOfDay(),
            self::Contract => $ended?->copy()->endOfYear()->addYears(6)->startOfDay(),
            self::Education => $ended?->copy()->addYears(6),
            self::Payroll => $ended?->copy()->endOfYear()->addYears(11)->startOfDay(),
            self::Safety => $origin->copy()->addYears(6),
            self::Pension => $ended?->copy()->addYears(40),
            self::OtherFo => $ended?->copy()->addYears(6),
            self::Other => $origin->copy()->addYears(6),
        };
    }
}
