<?php

namespace App\Enums;

enum ReminderKind: string
{
    case Expiry = 'expiry';
    case MissingOut = 'missing_out';
    case Incomplete = 'incomplete';

    public function label(): string
    {
        return match ($this) {
            self::Expiry => 'Istek dokumenata',
            self::MissingOut => 'Zaboravljena odjava',
            self::Incomplete => 'Nekompletan slog',
        };
    }
}
