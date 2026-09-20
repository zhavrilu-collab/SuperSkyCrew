<?php

namespace App\Enums;

enum InternalActKind: string
{
    case Rulebook = 'pravilnik';
    case Code = 'kodeks';
    case Decision = 'odluka';
    case Other = 'ostalo';

    public function label(): string
    {
        return match ($this) {
            self::Rulebook => 'Pravilnik',
            self::Code => 'Kodeks',
            self::Decision => 'Odluka',
            self::Other => 'Ostalo',
        };
    }
}
