<?php

namespace App\Support;

class DocumentMergeFields
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            '{{organizacija}}' => 'Naziv organizacije',
            '{{organizacija_oib}}' => 'OIB organizacije',
            '{{grad}}' => 'Sjedište / grad',
            '{{ime}}' => 'Ime',
            '{{prezime}}' => 'Prezime',
            '{{ime_prezime}}' => 'Ime i prezime',
            '{{oib}}' => 'OIB',
            '{{spol}}' => 'Spol',
            '{{rodjenje}}' => 'Datum rođenja',
            '{{drzavljanstvo}}' => 'Državljanstvo',
            '{{prebivaliste}}' => 'Prebivalište',
            '{{status}}' => 'Status',
            '{{radno_mjesto}}' => 'Radno mjesto',
            '{{rad1g}}' => 'RAD1G',
            '{{odjel}}' => 'Odjel',
            '{{lokacija}}' => 'Lokacija',
            '{{mt}}' => 'Mjesto troška',
            '{{ugovor}}' => 'Vrsta ugovora',
            '{{broj_ugovora}}' => 'Broj ugovora',
            '{{tjedni_sati}}' => 'Tjedni sati',
            '{{probni}}' => 'Probni rad do',
            '{{pocetak}}' => 'Početak rada',
            '{{prestanak}}' => 'Prestanak',
            '{{fond_go}}' => 'Fond GO (dana)',
            '{{djeca}}' => 'Djeca (GO)',
            '{{iban}}' => 'IBAN',
            '{{ustupitelj}}' => 'Ustupitelj / agencija',
            '{{znr}}' => 'Obavezan ZNR pregled',
            '{{lijecnicki}}' => 'Istek liječničkog',
            '{{dozvola}}' => 'Istek dozvole',
            '{{datum}}' => 'Današnji datum',
            '{{go_broj}}' => 'Klasa rješenja GO',
            '{{go_od}}' => 'GO od',
            '{{go_do}}' => 'GO do',
            '{{go_dani}}' => 'GO radni dani',
            '{{go_datumi}}' => 'Datumi GO',
            '{{go_godina}}' => 'Godina GO',
            '{{go_preostalo}}' => 'Preostalo GO',
            '{{go_staro}}' => 'Preostalo staro GO',
            '{{go_novo}}' => 'Preostalo novo GO',
            '{{potpisnik}}' => 'Potpisnik / odobravatelj',
        ];
    }
}
