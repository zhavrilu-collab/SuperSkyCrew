<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CourtRegisterLookupService
{
    public function __construct(
        private readonly SudregApiClient $client,
    ) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2 || ! $this->isConfigured()) {
            return [];
        }

        try {
            $digits = preg_replace('/\D+/', '', $query) ?? '';

            if (strlen($digits) === 11) {
                $detail = $this->subject('oib', $digits);

                return $detail === null ? [] : [$detail];
            }

            if (strlen($digits) === 9 && strlen($digits) === strlen(preg_replace('/\s+/', '', $query) ?? '')) {
                $detail = $this->subject('mbs', $digits);

                return $detail === null ? [] : [$detail];
            }

            $rows = $this->normalizeList($this->client->get('subjekti', [
                'tvrtka_naziv' => $query,
                'only_active' => '1',
                'limit' => $limit,
            ]));

            $results = [];
            foreach ($rows as $row) {
                if (count($results) >= $limit) {
                    break;
                }
                if (! is_array($row)) {
                    continue;
                }
                $mapped = $this->mapSubject($row);
                if ($mapped['name'] === '' || $mapped['oib'] === '') {
                    $mbs = $this->padMbs($row['potpuni_mbs'] ?? $row['mbs'] ?? null);
                    $oib = $this->padOib($row['potpuni_oib'] ?? $row['oib'] ?? null);
                    $detail = $mbs !== ''
                        ? $this->subject('mbs', $mbs)
                        : ($oib !== '' ? $this->subject('oib', $oib) : null);
                    if ($detail !== null) {
                        $mapped = $detail;
                    }
                }
                if ($mapped['name'] === '' && $mapped['oib'] === '') {
                    continue;
                }
                $results[] = $mapped;
            }

            return $results;
        } catch (\Throwable $exception) {
            Log::warning('Pretraga Sudskog registra nije uspjela.', [
                'query' => $query,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function subject(string $type, string $identifier): ?array
    {
        $identifier = preg_replace('/\s+/', '', $identifier) ?? '';
        if ($identifier === '') {
            return null;
        }

        $cacheKey = 'sudreg.subject.'.$type.'.'.$identifier;

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $payload = $this->client->get('detalji_subjekta', [
            'tip_identifikatora' => $type,
            'identifikator' => $identifier,
            'expand_relations' => '1',
            'only_active' => '1',
        ]);

        $row = $this->firstObject($payload);
        if ($row === null) {
            return null;
        }

        $mapped = $this->mapSubject($row);
        if ($mapped['name'] === '' && $mapped['oib'] === '') {
            return null;
        }

        Cache::put($cacheKey, $mapped, now()->addDay());

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapSubject(array $row): array
    {
        $tvrtka = is_array($row['tvrtka'] ?? null) ? $row['tvrtka'] : [];
        $sjediste = is_array($row['sjediste'] ?? null) ? $row['sjediste'] : [];
        $emails = $row['email_adrese'] ?? [];
        $email = '';
        if (is_array($emails)) {
            $first = $emails[0] ?? null;
            if (is_array($first)) {
                $email = (string) ($first['adresa'] ?? '');
            }
        }

        $house = trim((string) ($sjediste['kucni_broj'] ?? '').(string) ($sjediste['kucni_podbroj'] ?? ''));
        $street = trim(trim((string) ($sjediste['ulica'] ?? '')).($house !== '' ? ' '.$house : ''));
        $city = (string) ($sjediste['naziv_naselja'] ?? $sjediste['naziv_opcine'] ?? '');
        $postal = $sjediste['postanski_broj'] ?? null;

        $short = '';
        if (is_array($row['skracena_tvrtka'] ?? null)) {
            $short = (string) ($row['skracena_tvrtka']['ime'] ?? '');
        }

        return [
            'name' => (string) ($tvrtka['ime'] ?? $row['ime'] ?? $row['tvrtka_kod_brisanja'] ?? ''),
            'short_name' => (string) ($tvrtka['naznaka_imena'] ?? $short),
            'oib' => $this->padOib($row['potpuni_oib'] ?? $row['oib'] ?? null),
            'mbs' => $this->padMbs($row['potpuni_mbs'] ?? $row['mbs'] ?? null),
            'city' => $city,
            'street' => $street,
            'postal_city' => trim(($postal ? $postal.' ' : '').$city),
            'email' => $email,
            'nkd' => isset($row['glavna_djelatnost']) ? (string) $row['glavna_djelatnost'] : '',
            'founded_on' => $this->dateOnly($row['datum_osnivanja'] ?? null),
            'status' => 'AKTIVAN',
        ];
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $payload
     * @return list<mixed>
     */
    private function normalizeList(array $payload): array
    {
        if ($payload === []) {
            return [];
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        foreach (['items', 'data', 'subjekti'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return array_values($payload[$key]);
            }
        }

        return [$payload];
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function firstObject(array $payload): ?array
    {
        $list = $this->normalizeList($payload);
        $first = $list[0] ?? null;

        return is_array($first) ? $first : null;
    }

    private function padOib(mixed $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        return $digits === '' ? '' : str_pad($digits, 11, '0', STR_PAD_LEFT);
    }

    private function padMbs(mixed $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        return $digits === '' ? '' : str_pad($digits, 9, '0', STR_PAD_LEFT);
    }

    private function dateOnly(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return substr($value, 0, 10);
    }
}
