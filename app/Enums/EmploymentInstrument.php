<?php

namespace App\Enums;

enum EmploymentInstrument: string
{
    case EmploymentContract = 'uor';
    case Annex = 'aneks';
    case ServiceContract = 'uod';
    case Other = 'ostalo';

    public function label(): string
    {
        return match ($this) {
            self::EmploymentContract => 'Ugovor o radu',
            self::Annex => 'Aneks',
            self::ServiceContract => 'Ugovor o djelu',
            self::Other => 'Ostalo',
        };
    }

    public function printTitle(): string
    {
        return match ($this) {
            self::EmploymentContract => 'UGOVOR O RADU',
            self::Annex => 'ANEKS UGOVORA O RADU',
            self::ServiceContract => 'UGOVOR O DJELU',
            self::Other => 'UGOVOR',
        };
    }
}
