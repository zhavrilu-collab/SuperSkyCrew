<?php

namespace App\Enums;

enum CalendarLevel: string
{
    case Organization = 'organization';
    case Department = 'department';
    case JobPosition = 'job_position';
    case Person = 'person';

    public function label(): string
    {
        return match ($this) {
            self::Person => 'Radnik',
            self::JobPosition => 'Radno mjesto',
            self::Department => 'Odjel',
            self::Organization => 'Organizacija',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Person => 1,
            self::JobPosition => 2,
            self::Department => 3,
            self::Organization => 4,
        };
    }
}
