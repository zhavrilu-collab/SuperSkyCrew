<?php

namespace App\Enums;

enum OrganizationType: string
{
    case Company = 'company';
    case Craft = 'craft';
    case Nonprofit = 'nonprofit';

    public const CRAFTS_REGISTER_SEARCH_URL = 'https://pretrazivac-obrta.gov.hr/';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Tvrtka',
            self::Craft => 'Obrt',
            self::Nonprofit => 'Udruga / NPO',
        };
    }

    public function nameLabel(): string
    {
        return match ($this) {
            self::Company => 'Naziv tvrtke',
            self::Craft => 'Naziv obrta',
            self::Nonprofit => 'Naziv udruge',
        };
    }

    public function oibHint(): string
    {
        return match ($this) {
            self::Craft => 'OIB obrtnika (fizičke osobe). Obrt nema vlastiti OIB.',
            default => '11 znamenki, bez razmaka.',
        };
    }

    public function registryNumberLabel(): string
    {
        return match ($this) {
            self::Craft => 'Registarski broj obrta',
            default => 'MBS',
        };
    }

    public function registryNumberHint(): string
    {
        return match ($this) {
            self::Craft => 'Broj iz izvatka Obrtnog registra. Nije MBS — DZS ga obrtima ne dodjeljuje.',
            self::Company => 'Matični broj subjekta iz Sudskog registra.',
            self::Nonprofit => 'Po potrebi.',
        };
    }

    public function usesCourtRegister(): bool
    {
        return $this === self::Company;
    }

    public function usesCraftsRegister(): bool
    {
        return $this === self::Craft;
    }
}
