<?php

namespace App\Services;

use App\Enums\ContractType;
use App\Enums\PersonStatus;
use App\Models\Organization;
use App\Models\Person;
use App\Support\CroatianOib;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

class PeopleImportService
{
    public const MAX_ROWS = 500;

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return [
            'ime',
            'prezime',
            'oib',
            'spol',
            'datum_rodenja',
            'drzavljanstvo',
            'prebivaliste',
            'status',
            'vrsta_ugovora',
            'pocetak',
            'prestanak',
            'radno_mjesto',
        ];
    }

    /**
     * @return array{created: int, updated: int, skipped: list<array{row: int, reason: string}>}
     */
    public function import(Organization $organization, UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['created' => 0, 'updated' => 0, 'skipped' => [['row' => 0, 'reason' => 'Datoteka se ne može pročitati.']]];
        }

        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);

            return ['created' => 0, 'updated' => 0, 'skipped' => [['row' => 0, 'reason' => 'CSV je prazan.']]];
        }

        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first) ?? $first;
        $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        $headerCells = str_getcsv($first, $delimiter);
        $map = $this->headerMap($headerCells);

        if (! isset($map['ime']) || ! isset($map['prezime'])) {
            fclose($handle);

            return ['created' => 0, 'updated' => 0, 'skipped' => [['row' => 1, 'reason' => 'Nedostaju stupci ime i prezime.']]];
        }

        $created = 0;
        $updated = 0;
        $skipped = [];
        $rowNumber = 1;

        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;
            if ($rowNumber - 1 > self::MAX_ROWS) {
                $skipped[] = ['row' => $rowNumber, 'reason' => 'Limit je '.self::MAX_ROWS.' redaka.'];
                break;
            }
            if ($this->rowEmpty($cells)) {
                continue;
            }

            $result = $this->upsertRow($organization, $map, $cells);
            if ($result === 'created') {
                $created++;
            } elseif ($result === 'updated') {
                $updated++;
            } else {
                $skipped[] = ['row' => $rowNumber, 'reason' => $result];
            }
        }

        fclose($handle);

        return compact('created', 'updated', 'skipped');
    }

    /**
     * @param  list<string|null>  $headerCells
     * @return array<string, int>
     */
    private function headerMap(array $headerCells): array
    {
        $aliases = [
            'ime' => 'ime',
            'prezime' => 'prezime',
            'oib' => 'oib',
            'spol' => 'spol',
            'datum rođenja' => 'datum_rodenja',
            'datum rodenja' => 'datum_rodenja',
            'datum_rodenja' => 'datum_rodenja',
            'državljanstvo' => 'drzavljanstvo',
            'drzavljanstvo' => 'drzavljanstvo',
            'prebivalište' => 'prebivaliste',
            'prebivaliste' => 'prebivaliste',
            'status' => 'status',
            'vrsta ugovora' => 'vrsta_ugovora',
            'vrsta_ugovora' => 'vrsta_ugovora',
            'početak' => 'pocetak',
            'pocetak' => 'pocetak',
            'prestanak' => 'prestanak',
            'radno mjesto' => 'radno_mjesto',
            'radno_mjesto' => 'radno_mjesto',
        ];

        $map = [];
        foreach ($headerCells as $index => $cell) {
            $key = $this->normalizeHeader((string) $cell);
            if ($key === '' || ! isset($aliases[$key])) {
                continue;
            }
            $map[$aliases[$key]] = $index;
        }

        return $map;
    }

    private function normalizeHeader(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = str_replace('.', '', $value);

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    /**
     * @param  array<string, int>  $map
     * @param  list<string|null>  $cells
     */
    private function upsertRow(Organization $organization, array $map, array $cells): string
    {
        $first = trim($this->cell($map, $cells, 'ime'));
        $last = trim($this->cell($map, $cells, 'prezime'));
        if ($first === '' || $last === '') {
            return 'Nedostaje ime ili prezime.';
        }

        $oib = preg_replace('/\s+/', '', $this->cell($map, $cells, 'oib')) ?: null;
        if ($oib !== null && $oib !== '' && ! CroatianOib::isValid($oib)) {
            return 'OIB nije ispravan.';
        }
        if ($oib === '') {
            $oib = null;
        }

        $status = $this->parseStatus($this->cell($map, $cells, 'status'));
        if ($status === null) {
            return 'Nepoznat status.';
        }

        $payload = [
            'first_name' => $first,
            'last_name' => $last,
            'oib' => $oib,
            'gender' => $this->parseGender($this->cell($map, $cells, 'spol')),
            'date_of_birth' => $this->parseDate($this->cell($map, $cells, 'datum_rodenja')),
            'citizenship' => $this->nullable($this->cell($map, $cells, 'drzavljanstvo')),
            'residence' => $this->nullable($this->cell($map, $cells, 'prebivaliste')),
            'status' => $status,
            'contract_type' => $this->parseContract($this->cell($map, $cells, 'vrsta_ugovora')),
            'started_at' => $this->parseDate($this->cell($map, $cells, 'pocetak')),
            'ended_at' => $this->parseDate($this->cell($map, $cells, 'prestanak')),
            'job_title' => $this->nullable($this->cell($map, $cells, 'radno_mjesto')),
        ];

        $existing = null;
        if ($oib !== null) {
            $existing = Person::query()
                ->forOrganization($organization)
                ->where('oib', $oib)
                ->first();
        }

        if ($existing) {
            $existing->fill(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
            $existing->oib = $oib;
            $existing->status = $status;
            $existing->save();

            return 'updated';
        }

        Person::query()->create($payload + [
            'organization_id' => $organization->id,
            'annual_leave_days' => $organization->annual_leave_base_days ?? 20,
        ]);

        return 'created';
    }

    /**
     * @param  array<string, int>  $map
     * @param  list<string|null>  $cells
     */
    private function cell(array $map, array $cells, string $key): string
    {
        if (! isset($map[$key])) {
            return '';
        }

        return trim((string) ($cells[$map[$key]] ?? ''));
    }

    /**
     * @param  list<string|null>  $cells
     */
    private function rowEmpty(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function parseGender(string $value): ?string
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            'm', 'muški', 'muski', 'm.' => 'm',
            'z', 'ž', 'ženski', 'zenski', 'ž.' => 'z',
            'x', 'ostalo' => 'x',
            '' => null,
            default => null,
        };
    }

    private function parseStatus(string $value): ?PersonStatus
    {
        $value = trim($value);
        if ($value === '') {
            return PersonStatus::Employee;
        }

        $enum = PersonStatus::tryFrom(str_replace([' ', '-'], '_', mb_strtolower($value)));
        if ($enum) {
            return $enum;
        }

        foreach (PersonStatus::cases() as $case) {
            if (mb_strtolower($case->label()) === mb_strtolower($value)) {
                return $case;
            }
        }

        return null;
    }

    private function parseContract(string $value): ?ContractType
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $enum = ContractType::tryFrom(str_replace([' ', '-'], '_', mb_strtolower($value)));
        if ($enum) {
            return $enum;
        }

        foreach (ContractType::cases() as $case) {
            if (mb_strtolower($case->label()) === mb_strtolower($value)) {
                return $case;
            }
        }

        return null;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim(str_replace('.', '.', $value));
        $value = rtrim($value, '.');
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd.m.Y', 'd.m.y', 'd/m/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value, config('app.timezone'));
                if ($date !== false) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value, config('app.timezone'))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
