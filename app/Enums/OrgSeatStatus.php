<?php

namespace App\Enums;

enum OrgSeatStatus: string
{
    case Open = 'open';
    case Filled = 'filled';
    case Hiring = 'hiring';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Slobodna',
            self::Filled => 'Popunjena',
            self::Hiring => 'Zapošljavanje',
            self::Closed => 'Zatvorena',
        };
    }
}
