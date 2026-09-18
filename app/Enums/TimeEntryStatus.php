<?php

namespace App\Enums;

enum TimeEntryStatus: string
{
    case Draft = 'draft';
    case Complete = 'complete';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'U tijeku',
            self::Complete => 'Zatvoren',
            self::Locked => 'Zaključan',
        };
    }
}
