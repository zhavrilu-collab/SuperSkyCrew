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

    /** @return array<string, string> */
    public static function tabs(): array
    {
        return [
            self::PRAVNE => 'Pravne osobe',
            self::POSLOVNICE => 'Poslovnice',
            self::TROSKOVI => 'Mjesta troška',
            self::POSLOVNA => 'Poslovna struktura',
            self::FUNKCIJSKA => 'Funkcijski ustroj',
            self::MJESTA => 'Radna mjesta',
            self::ORGANIGRAM => 'Organigram',
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
}
