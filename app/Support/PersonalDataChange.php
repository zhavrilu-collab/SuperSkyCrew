<?php

namespace App\Support;

use App\Models\Person;

class PersonalDataChange
{
    /** @var array<string, string> */
    public const FIELDS = [
        'first_name' => 'Ime',
        'last_name' => 'Prezime',
        'oib' => 'OIB',
        'gender' => 'Spol',
        'date_of_birth' => 'Datum rođenja',
        'citizenship' => 'Državljanstvo',
        'residence' => 'Prebivalište / boravište',
    ];

    public static function current(Person $person): array
    {
        return [
            'first_name' => $person->first_name,
            'last_name' => $person->last_name,
            'oib' => $person->oib,
            'gender' => $person->gender,
            'date_of_birth' => $person->date_of_birth?->toDateString(),
            'citizenship' => $person->citizenship,
            'residence' => $person->residence,
        ];
    }

    public static function display(?string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($field === 'gender') {
            return match ($value) {
                'm' => 'M',
                'z' => 'Ž',
                'x' => 'Ostalo',
                default => (string) $value,
            };
        }

        return (string) $value;
    }

    /**
     * @param  array<string, mixed>  $proposed
     * @return array<string, array{from: mixed, to: mixed}>
     */
    public static function diff(Person $person, array $proposed): array
    {
        $current = self::current($person);
        $changes = [];

        foreach (array_keys(self::FIELDS) as $field) {
            if (! array_key_exists($field, $proposed)) {
                continue;
            }

            $to = $proposed[$field];
            if ($to === '') {
                $to = null;
            }

            $from = $current[$field] ?? null;
            if ((string) ($from ?? '') === (string) ($to ?? '')) {
                continue;
            }

            $changes[$field] = ['from' => $from, 'to' => $to];
        }

        return $changes;
    }
}
