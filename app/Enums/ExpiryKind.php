<?php

namespace App\Enums;

enum ExpiryKind: string
{
    case WorkPermit = 'work_permit';
    case Medical = 'medical';
    case Certificate = 'certificate';
    case Qualification = 'qualification';
    case FixedTerm = 'fixed_term';

    public function label(): string
    {
        return match ($this) {
            self::WorkPermit => 'Dozvola boravka/rada',
            self::Medical => 'Liječnički pregled',
            self::Certificate => 'Certifikat / atest',
            self::Qualification => 'Obrazovanje / certifikat',
            self::FixedTerm => 'UOR na određeno',
        };
    }
}
