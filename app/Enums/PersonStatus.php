<?php

namespace App\Enums;

enum PersonStatus: string
{
    case Candidate = 'candidate';
    case Employee = 'employee';
    case Assigned = 'assigned';
    case OtherFo = 'other_fo';
    case Contractor = 'contractor';
    case Volunteer = 'volunteer';
    case Executive = 'executive';
    case Former = 'former';

    public function label(): string
    {
        return match ($this) {
            self::Candidate => 'Kandidat',
            self::Employee => 'Radnik',
            self::Assigned => 'Ustupljeni radnik',
            self::OtherFo => 'Druga fizička osoba',
            self::Contractor => 'Honorarac',
            self::Volunteer => 'Volonter',
            self::Executive => 'Rukovodeća osoba',
            self::Former => 'Bivši',
        };
    }

    public function clocksIn(): bool
    {
        return in_array($this, [
            self::Employee,
            self::Assigned,
            self::OtherFo,
            self::Contractor,
            self::Executive,
        ], true);
    }
}
