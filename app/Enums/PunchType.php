<?php

namespace App\Enums;

enum PunchType: string
{
    case In = 'in';
    case Out = 'out';
    case BreakStart = 'break_start';
    case BreakEnd = 'break_end';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Prijava',
            self::Out => 'Odjava',
            self::BreakStart => 'Početak pauze',
            self::BreakEnd => 'Kraj pauze',
        };
    }
}
