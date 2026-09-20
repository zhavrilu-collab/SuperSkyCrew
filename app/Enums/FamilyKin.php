<?php

namespace App\Enums;

enum FamilyKin: string
{
    case Spouse = 'spouse';
    case Child = 'child';
    case Parent = 'parent';
    case Sibling = 'sibling';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spouse => 'Supružnik',
            self::Child => 'Dijete',
            self::Parent => 'Roditelj',
            self::Sibling => 'Brat / sestra',
            self::Other => 'Ostalo',
        };
    }
}
