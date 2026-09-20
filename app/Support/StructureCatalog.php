<?php

namespace App\Support;

class StructureCatalog
{
    public const PRAVNE = 'pravne';

    public const POSLOVNICE = 'poslovnice';

    public const TROSKOVI = 'troskovi';

    public const POSLOVNA = 'poslovna';

    public const FUNKCIJSKA = 'funkcijska';

    public const MJESTA = 'mjesta';

    public const ORGANIGRAM = 'organigram';

    /** @var list<string> */
    public const KEYS = [
        self::PRAVNE,
        self::POSLOVNICE,
        self::TROSKOVI,
        self::POSLOVNA,
        self::FUNKCIJSKA,
        self::MJESTA,
        self::ORGANIGRAM,
    ];

    /** @var list<string> */
    public const PROFIL_KEYS = [
        self::PRAVNE,
        self::POSLOVNICE,
        self::TROSKOVI,
    ];

    /** @var list<string> */
    public const USTROJ_KEYS = [
        self::POSLOVNA,
        self::FUNKCIJSKA,
        self::MJESTA,
        self::ORGANIGRAM,
    ];

    /** @return array<string, string> */
    public static function tabs(): array
    {
        return [
            self::PRAVNE => 'Članice grupacije',
            self::POSLOVNICE => 'Poslovnice',
            self::TROSKOVI => 'Mjesta troška',
            self::POSLOVNA => 'Poslovni ustroj',
            self::FUNKCIJSKA => 'Funkcionalni ustroj',
            self::MJESTA => 'Radna mjesta',
            self::ORGANIGRAM => 'Organizacijska shema',
        ];
    }

    public static function resolve(?string $katalog): string
    {
        if ($katalog === 'shema' || $katalog === 'odjeli') {
            return self::FUNKCIJSKA;
        }

        if (in_array($katalog, self::KEYS, true)) {
            return $katalog;
        }

        return self::POSLOVNA;
    }

    public static function isProfil(string $katalog): bool
    {
        return in_array($katalog, self::PROFIL_KEYS, true);
    }

    public static function isUstrojTvrtke(string $katalog): bool
    {
        return in_array($katalog, self::USTROJ_KEYS, true);
    }
}
