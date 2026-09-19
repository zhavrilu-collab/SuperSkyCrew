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

        if ($isOwner || $canHr) {
            $tabs[] = ['key' => self::TAB_ORGANIZACIJA, 'label' => 'Organizacija', 'section' => $isOwner ? 'osnovni-podaci' : 'ustroj'];
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
            default => 'Osnovni podaci, izgled i ustroj organizacije.',
        };
    }

    /** @return array<string, string> */
    public static function sectionsFor(string $tab, bool $isOwner = false): array
    {
        return match ($tab) {
            self::TAB_ORGANIZACIJA => $isOwner ? [
                'osnovni-podaci' => 'Osnovni podaci',
                'izgled' => 'Izgled',
                'ustroj' => 'Ustroj',
            ] : [
                'ustroj' => 'Ustroj',
            ],
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
            self::TAB_ORGANIZACIJA => $isOwner ? 'osnovni-podaci' : 'ustroj',
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

        $valid = array_keys(self::sectionsFor($tab, $isOwner));
        if ($valid === []) {
            return '';
        }

        if ($section !== '' && in_array($section, $valid, true)) {
            return $section;
        }

        return self::defaultSection($tab, $isOwner);
    }
}
