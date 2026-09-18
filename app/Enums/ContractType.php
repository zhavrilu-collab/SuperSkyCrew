<?php

namespace App\Enums;

enum ContractType: string
{
    case Indefinite = 'indefinite';
    case FixedTerm = 'fixed_term';
    case PartTime = 'part_time';
    case Additional = 'additional';
    case Remote = 'remote';
    case OffSite = 'off_site';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Indefinite => 'Neodređeno',
            self::FixedTerm => 'Određeno',
            self::PartTime => 'Nepuno radno vrijeme',
            self::Additional => 'Dopunski rad',
            self::Remote => 'Rad na daljinu',
            self::OffSite => 'Izdvojeno mjesto rada',
            self::Other => 'Ostalo',
        };
    }
}
