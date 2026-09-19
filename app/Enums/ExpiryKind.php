<?php

namespace App\Enums;

enum ExpiryKind: string
{
    case WorkPermit = 'work_permit';
    case Medical = 'medical';
    case Certificate = 'certificate';
    case Qualification = 'qualification';
    case Dossier = 'dossier';
    case FixedTerm = 'fixed_term';
    case Trial = 'trial';

    public function label(): string
    {
        return match ($this) {
            self::WorkPermit => 'Dozvola boravka/rada',
            self::Medical => 'Liječnički pregled',
            self::Certificate => 'Certifikat / atest',
            self::Qualification => 'Obrazovanje / certifikat',
            self::Dossier => 'Dokument dosjea',
            self::FixedTerm => 'UOR na određeno',
            self::Trial => 'Probni rad',
        };
    }
}
