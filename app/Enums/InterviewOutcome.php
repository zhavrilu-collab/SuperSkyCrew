<?php

namespace App\Enums;

enum InterviewOutcome: string
{
    case Scheduled = 'scheduled';
    case Held = 'held';
    case Positive = 'positive';
    case Negative = 'negative';
    case Hold = 'hold';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Zakazan',
            self::Held => 'Održan',
            self::Positive => 'Preporuka',
            self::Negative => 'Odbijen',
            self::Hold => 'Na čekanju',
        };
    }
}
