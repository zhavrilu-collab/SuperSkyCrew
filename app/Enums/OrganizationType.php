<?php

namespace App\Enums;

enum OrganizationType: string
{
    case Company = 'company';
    case Nonprofit = 'nonprofit';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Tvrtka / obrt',
            self::Nonprofit => 'Udruga / NPO',
        };
    }
}
