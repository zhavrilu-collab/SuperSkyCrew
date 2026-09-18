<?php

namespace App\Enums;

enum QualificationKind: string
{
    case Education = 'education';
    case Exam = 'exam';
    case Course = 'course';
    case License = 'license';
    case Certificate = 'certificate';

    public function label(): string
    {
        return match ($this) {
            self::Education => 'Škola / fakultet',
            self::Exam => 'Ispit',
            self::Course => 'Tečaj',
            self::License => 'Licenca',
            self::Certificate => 'Certifikat / atest',
        };
    }
}
