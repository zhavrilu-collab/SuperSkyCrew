<?php

namespace App\Enums;

enum DocumentKind: string
{
    case WrittenReview = 'pisani_pregled';
    case TimeRecord = 'evidencija_rv';
    case LeaveDecision = 'rjesenje_go';
    case EmploymentContract = 'uor';
    case MedicalReferral = 'uputnica';
    case Other = 'ostalo';

    public function label(): string
    {
        return match ($this) {
            self::WrittenReview => 'Pisani pregled (čl. 4.)',
            self::TimeRecord => 'Evidencija radnog vremena',
            self::LeaveDecision => 'Rješenje o GO',
            self::EmploymentContract => 'Ugovor o radu',
            self::MedicalReferral => 'Uputnica za liječnički',
            self::Other => 'Ostalo',
        };
    }
}
