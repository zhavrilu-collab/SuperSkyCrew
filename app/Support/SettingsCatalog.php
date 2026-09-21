<?php

namespace App\Support;

class SettingsCatalog
{
    public const TAB_ORGANIZACIJA = 'organizacija';

    public const TAB_KADAR = 'kadar';

    public const TAB_VRIJEME = 'vrijeme';

    public const TAB_ODOBRENJA = 'odobrenja';

    public const TAB_PRISTUP = 'pristup';

    public const TAB_PODACI = 'podaci';

    public const TAB_PRETPLATA = 'pretplata';

    /**
     * @return list<array{key: string, label: string, section: ?string}>
     */
    public static function catalogTabs(bool $isOwner, bool $canHr, bool $canAccess, bool $canTeam): array
    {
        $tabs = [];

        if ($isOwner) {
            $tabs[] = ['key' => self::TAB_ORGANIZACIJA, 'label' => 'Organizacija', 'section' => 'izgled'];
        }
        if ($canHr) {
            $tabs[] = ['key' => self::TAB_KADAR, 'label' => 'Kadrovi', 'section' => 'vrste-dokumenata'];
        }
        if ($canAccess) {
            $tabs[] = ['key' => self::TAB_VRIJEME, 'label' => 'Vrijeme', 'section' => 'sifarnik'];
            $tabs[] = ['key' => self::TAB_ODOBRENJA, 'label' => 'Odobrenja', 'section' => 'radni-slijedovi'];
        }
        if ($canTeam) {
            $tabs[] = ['key' => self::TAB_PRISTUP, 'label' => 'Pristup', 'section' => 'korisnici'];
        }
        if ($canHr || $canAccess) {
            $tabs[] = ['key' => self::TAB_PODACI, 'label' => 'Podaci', 'section' => 'izvoz'];
        }
        if ($isOwner) {
            $tabs[] = ['key' => self::TAB_PRETPLATA, 'label' => 'Pretplata', 'section' => null];
        }

        return $tabs;
    }

    public static function tabLabel(string $tab): string
    {
        return match ($tab) {
            self::TAB_KADAR => 'Kadrovi',
            self::TAB_VRIJEME => 'Vrijeme',
            self::TAB_ODOBRENJA => 'Odobrenja',
            self::TAB_PRISTUP => 'Pristup',
            self::TAB_PODACI => 'Podaci',
            self::TAB_PRETPLATA => 'Pretplata',
            default => 'Organizacija',
        };
    }

    public static function tabDescription(string $tab): string
    {
        return match ($tab) {
            self::TAB_KADAR => 'Vrste dokumenata, predlošci akata, pragovi isteka, zadržavanje dosjea i volonteri.',
            self::TAB_VRIJEME => 'Šifrarnik sati, smjene, kalendari, lokacije/kanali, politika GO, zaključavanje i e-mail podsjetnici.',
            self::TAB_ODOBRENJA => 'Definicije radnih slijedova i tablica koraka odobrenja.',
            self::TAB_PRISTUP => 'Korisnici aplikacije, pozivnice i prava po ulogama.',
            self::TAB_PODACI => 'Izvoz i uvoz kadra, šihterica, inspekcijski paket i revizijski trag.',
            self::TAB_PRETPLATA => 'Plan i status pretplate. Naplata se vodi u Core konzoli.',
            default => 'Izgled i tema organizacije.',
        };
    }

    public static function sectionLabel(string $tab, string $section): string
    {
        if ($section === '') {
            return self::tabLabel($tab);
        }

        return self::sectionsFor($tab, true)[$section]
            ?? match ($section) {
                'ustroj' => 'Ustroj',
                'osnovni-podaci' => 'Osnovni podaci',
                default => self::tabLabel($tab),
            };
    }

    public static function sectionDescription(string $tab, string $section): string
    {
        return match ($section) {
            'izgled' => 'Tema i logotip organizacije.',
            'vrste-dokumenata' => 'Vrste dokumenata dosjea. Sistemske vrste se ne brišu.',
            'predlosci' => 'Ugrađeni ispisi i vlastiti Word/PDF predlošci.',
            'isteci' => 'Horizon upozorenja za UOR, dozvole, preglede i certifikate.',
            'zadrzavanje' => 'Klase čuvanja dosjea prema čl. 8.–9. i predloženo brisanje.',
            'volonteri' => 'Status volontera u kadru, odvojen od članova udruge.',
            'sifarnik' => 'Šifrarnik sati i odsutnosti s pisanim značenjem kratice.',
            'smjene' => 'Šifrarnik smjena. Tjedni plan ostaje u modulu Vrijeme.',
            'kalendari' => 'Pravila kalendara na četiri razine. Specifičnija pobjeđuje.',
            'lokacije' => 'Lokacije, geofence i kiosk kanali prijave.',
            'go-politika' => 'Osnovni fond GO i pragovi po stažu.',
            'zakljucavanje' => 'Zaključavanje obračunskog razdoblja.',
            'obavijesti' => 'E-mail podsjetnici za prijavu, plan i isteke.',
            'radni-slijedovi' => 'Koraci odobrenja po vrsti zahtjeva.',
            'korisnici' => 'Korisnici aplikacije i pozivnice.',
            'prava' => 'Matrica prava po ulogama.',
            'izvoz' => 'Izvoz kadra, šihterice i inspekcijski paket.',
            'uvoz' => 'Uvoz kadra iz CSV-a.',
            'trag' => 'Revizijski trag radnji u organizaciji.',
            default => self::tabDescription($tab),
        };
    }

    /**
     * @return list<array{label: ?string, items: list<array{tab: string, section: ?string, label: string, icon: ?string}>}>
     */
    public static function sidebarGroups(bool $isOwner, bool $canHr, bool $canAccess, bool $canTeam): array
    {
        $groups = [];

        if ($isOwner) {
            $groups[] = [
                'label' => null,
                'items' => [
                    ['tab' => self::TAB_ORGANIZACIJA, 'section' => 'izgled', 'label' => 'Izgled', 'icon' => 'palette'],
                ],
            ];
        }
        if ($canHr) {
            $groups[] = [
                'label' => 'Kadrovi',
                'items' => [
                    ['tab' => self::TAB_KADAR, 'section' => 'vrste-dokumenata', 'label' => 'Vrste dokumenata', 'icon' => 'folder'],
                    ['tab' => self::TAB_KADAR, 'section' => 'predlosci', 'label' => 'Predlošci', 'icon' => null],
                    ['tab' => self::TAB_KADAR, 'section' => 'isteci', 'label' => 'Isteci dokumenata', 'icon' => null],
                    ['tab' => self::TAB_KADAR, 'section' => 'zadrzavanje', 'label' => 'Zadržavanje', 'icon' => null],
                    ['tab' => self::TAB_KADAR, 'section' => 'volonteri', 'label' => 'Volonteri', 'icon' => null],
                ],
            ];
        }
        if ($canAccess) {
            $groups[] = [
                'label' => 'Vrijeme',
                'items' => [
                    ['tab' => self::TAB_VRIJEME, 'section' => 'sifarnik', 'label' => 'Šifrarnik sati', 'icon' => 'list'],
                    ['tab' => self::TAB_VRIJEME, 'section' => 'smjene', 'label' => 'Smjene', 'icon' => null],
                    ['tab' => self::TAB_VRIJEME, 'section' => 'kalendari', 'label' => 'Kalendari', 'icon' => null],
                    ['tab' => self::TAB_VRIJEME, 'section' => 'lokacije', 'label' => 'Lokacije i kanali', 'icon' => null],
                    ['tab' => self::TAB_VRIJEME, 'section' => 'go-politika', 'label' => 'GO politika', 'icon' => null],
                    ['tab' => self::TAB_VRIJEME, 'section' => 'zakljucavanje', 'label' => 'Zaključavanje', 'icon' => null],
                    ['tab' => self::TAB_VRIJEME, 'section' => 'obavijesti', 'label' => 'Obavijesti', 'icon' => null],
                ],
            ];
            $groups[] = [
                'label' => null,
                'items' => [
                    ['tab' => self::TAB_ODOBRENJA, 'section' => 'radni-slijedovi', 'label' => 'Radni slijedovi', 'icon' => 'flow'],
                ],
            ];
        }
        if ($canTeam) {
            $groups[] = [
                'label' => 'Pristup',
                'items' => [
                    ['tab' => self::TAB_PRISTUP, 'section' => 'korisnici', 'label' => 'Korisnici i pozivnice', 'icon' => 'key'],
                    ['tab' => self::TAB_PRISTUP, 'section' => 'prava', 'label' => 'Prava pristupa', 'icon' => null],
                ],
            ];
        }
        if ($canHr || $canAccess) {
            $groups[] = [
                'label' => 'Podaci',
                'items' => [
                    ['tab' => self::TAB_PODACI, 'section' => 'izvoz', 'label' => 'Izvoz', 'icon' => 'database'],
                    ['tab' => self::TAB_PODACI, 'section' => 'uvoz', 'label' => 'Uvoz', 'icon' => null],
                    ['tab' => self::TAB_PODACI, 'section' => 'trag', 'label' => 'Revizijski trag', 'icon' => null],
                ],
            ];
        }
        if ($isOwner) {
            $groups[] = [
                'label' => null,
                'items' => [
                    ['tab' => self::TAB_PRETPLATA, 'section' => null, 'label' => 'Pretplata', 'icon' => 'card'],
                ],
            ];
        }

        return $groups;
    }

    /** @return array<string, string> */
    public static function sectionsFor(string $tab, bool $isOwner = false): array
    {
        return match ($tab) {
            self::TAB_ORGANIZACIJA => $isOwner ? [
                'izgled' => 'Izgled',
            ] : [],
            self::TAB_KADAR => [
                'vrste-dokumenata' => 'Vrste dokumenata',
                'predlosci' => 'Predlošci',
                'isteci' => 'Isteci',
                'zadrzavanje' => 'Zadržavanje',
                'volonteri' => 'Volonteri',
            ],
            self::TAB_VRIJEME => [
                'sifarnik' => 'Šifrarnik sati',
                'smjene' => 'Smjene',
                'kalendari' => 'Kalendari',
                'lokacije' => 'Lokacije i kanali',
                'go-politika' => 'GO politika',
                'zakljucavanje' => 'Zaključavanje',
                'obavijesti' => 'Obavijesti',
            ],
            self::TAB_ODOBRENJA => [
                'radni-slijedovi' => 'Radni slijedovi',
            ],
            self::TAB_PRISTUP => [
                'korisnici' => 'Korisnici i pozivnice',
                'prava' => 'Prava pristupa',
            ],
            self::TAB_PODACI => [
                'izvoz' => 'Izvoz',
                'uvoz' => 'Uvoz',
                'trag' => 'Revizijski trag',
            ],
            default => [],
        };
    }

    public static function defaultSection(string $tab, bool $isOwner = false): string
    {
        return match ($tab) {
            self::TAB_ORGANIZACIJA => $isOwner ? 'izgled' : 'ustroj',
            self::TAB_KADAR => 'vrste-dokumenata',
            self::TAB_VRIJEME => 'sifarnik',
            self::TAB_ODOBRENJA => 'radni-slijedovi',
            self::TAB_PRISTUP => 'korisnici',
            self::TAB_PODACI => 'izvoz',
            default => '',
        };
    }

    public static function resolveSection(string $tab, string $section, bool $isOwner = false): string
    {
        if ($tab === self::TAB_PRETPLATA) {
            return '';
        }

        if ($tab === self::TAB_ORGANIZACIJA) {
            if ($section === 'ustroj') {
                return 'ustroj';
            }
            if ($section === 'osnovni-podaci') {
                return $isOwner ? 'osnovni-podaci' : self::defaultSection($tab, $isOwner);
            }
        }

        $valid = array_keys(self::sectionsFor($tab, $isOwner));
        if ($valid === []) {
            return self::defaultSection($tab, $isOwner);
        }

        if ($section !== '' && in_array($section, $valid, true)) {
            return $section;
        }

        return self::defaultSection($tab, $isOwner);
    }
}
