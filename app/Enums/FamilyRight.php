<?php

namespace App\Enums;

enum FamilyRight: string
{
    case Maternity = 'maternity';
    case Parental = 'parental';
    case Paternity = 'paternity';
    case Adoption = 'adoption';

    public function label(): string
    {
        return match ($this) {
            self::Maternity => 'Rodiljna',
            self::Parental => 'Roditeljska',
            self::Paternity => 'Očinska',
            self::Adoption => 'Posvojiteljska',
        };
    }
}
