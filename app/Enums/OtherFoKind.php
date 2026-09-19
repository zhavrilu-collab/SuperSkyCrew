<?php

namespace App\Enums;

enum OtherFoKind: string
{
    case Student = 'student';
    case Pupil = 'pupil';
    case Sor = 'sor';
    case WorkBasedLearning = 'work_based_learning';
    case PaidMinor = 'paid_minor';
    case PublicGood = 'public_good';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Pupil => 'Učenik (povremeni rad)',
            self::Sor => 'SOR bez radnog odnosa',
            self::WorkBasedLearning => 'Učenje temeljeno na radu',
            self::PaidMinor => 'Maloljetnik uz naplatu',
            self::PublicGood => 'Rad za opće dobro',
            self::Other => 'Ostalo',
        };
    }
}
