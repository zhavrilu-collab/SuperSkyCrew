<?php

namespace App\Enums;

enum GeofenceMode: string
{
    case Off = 'off';
    case Warn = 'warn';
    case Strict = 'strict';

    public function label(): string
    {
        return match ($this) {
            self::Off => 'Isključeno',
            self::Warn => 'Upozorenje',
            self::Strict => 'Strogo',
        };
    }
}
