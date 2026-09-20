<?php

namespace App\Support;

class ComingSoonCatalog
{
    public const SEGMENTS = 'segments';

    public const POSITIONS = 'positions';

    public const POSITION_PLAN = 'position-plan';

    public const COMPETENCIES = 'competencies';

    public const INTERNAL_ACTS = 'internal-acts';

    public const CONTRACTS = 'contracts';

    public const DOCUMENT_CREATOR = 'document-creator';

    public const FAMILY = 'family';

    /**
     * @return array<string, array{title: string, nav: string, lead: string}>
     */
    public static function all(): array
    {
        return [
            self::SEGMENTS => [
                'title' => 'Poslovni segmenti',
                'nav' => 'Ustroj tvrtke',
                'lead' => 'Podjela unutar veće grupacije koja posluje u više različitih industrija (npr. segment maloprodaje, segment proizvodnje, segment nekretnina). Služi za odvojeno praćenje profitabilnosti i strateško upravljanje.',
            ],
            self::POSITIONS => [
                'title' => 'Radne pozicije',
                'nav' => 'Ustroj tvrtke',
                'lead' => 'Točan broj otvorenih i popunjenih mjesta u nekom odjelu. Radno mjesto je npr. Voditelj projekta, a pozicije kažu da u Odjelu IT-ja postoje točno tri stolice za tu ulogu.',
            ],
            self::POSITION_PLAN => [
                'title' => 'Plan radnih pozicija',
                'nav' => 'Sistematizacija',
                'lead' => 'Upravljanje konkretnim stolicama na koje sjedaju ljudi. Ovdje se pozicije otvaraju, zatvaraju ili stavljaju u status zapošljavanja.',
            ],
            self::COMPETENCIES => [
                'title' => 'Kompetencije',
                'nav' => 'Sistematizacija',
                'lead' => 'Matrica vještina. Definiranje mekih i tvrdih vještina, jezika ili certifikata koji su potrebni za pojedina radna mjesta, što kasnije služi za ocjenjivanje zaposlenika.',
            ],
            self::INTERNAL_ACTS => [
                'title' => 'Interni akti',
                'nav' => 'Sistematizacija',
                'lead' => 'Pohrana i upravljanje službenim dokumentima tvrtke poput Pravilnika o radu, kodeksa ponašanja ili pravila o odijevanju s kojima zaposlenici moraju biti upoznati.',
            ],
            self::CONTRACTS => [
                'title' => 'Ugovori o radu',
                'nav' => 'Zaposlenici',
                'lead' => 'Evidencija svih ugovora i dodataka ugovorima (aneksa). Prati povijest ugovornih odnosa, ugovoreno radno vrijeme, trajanje probnog rada, bruto plaću, dane godišnjeg odmora i otkazne rokove.',
            ],
            self::DOCUMENT_CREATOR => [
                'title' => 'Izrada dokumenata',
                'nav' => 'Zaposlenici',
                'lead' => 'Alat za HR administratore za brzo generiranje ugovora o radu, aneksa, odluka o godišnjem odmoru ili potvrda o zaposlenju povlačenjem podataka iz profila radnika u predložak.',
            ],
            self::FAMILY => [
                'title' => 'Članovi obitelji',
                'nav' => 'Zaposlenici',
                'lead' => 'Evidencija članova obitelji i djece zaposlenika. U Hrvatskoj je to ključno zbog olakšica na porez na dohodak, dodatnih dana godišnjeg odmora, darivanja djece i kontakta za hitne slučajeve.',
            ],
        ];
    }

    /** @return array{title: string, nav: string, lead: string}|null */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
