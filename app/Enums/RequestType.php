<?php

namespace App\Enums;

enum RequestType: string
{
    case LeaveAnnual = 'leave_annual';
    case LeaveOther = 'leave_other';
    case Overtime = 'overtime';
    case PunchCorrection = 'punch_correction';
    case PersonalDataChange = 'personal_data';
    case ShiftSwap = 'shift_swap';

    public function label(): string
    {
        return match ($this) {
            self::LeaveAnnual => 'Godišnji odmor',
            self::LeaveOther => 'Odsutnost',
            self::Overtime => 'Prekovremeni',
            self::PunchCorrection => 'Ispravak prijave',
            self::PersonalDataChange => 'Promjena podataka',
            self::ShiftSwap => 'Zamjena smjene',
        };
    }

    public function defaultAbsenceCode(): ?string
    {
        return match ($this) {
            self::LeaveAnnual => 'GO',
            default => null,
        };
    }
}
