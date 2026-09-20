<?php

namespace App\Enums;

enum CompetencyKind: string
{
    case Soft = 'soft';
    case Hard = 'hard';
    case Language = 'language';
    case Certificate = 'certificate';

    public function label(): string
    {
        return match ($this) {
            self::Soft => 'Meka vještina',
            self::Hard => 'Tvrda vještina',
            self::Language => 'Jezik',
            self::Certificate => 'Certifikat',
        };
    }
}
