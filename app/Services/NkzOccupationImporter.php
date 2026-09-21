<?php

namespace App\Services;

use App\Models\NkzOccupation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NkzOccupationImporter
{
    public static function snapshotPath(): string
    {
        return database_path('data/nkz10-rad1g.json');
    }

    /**
     * @return list<array{code: string, title: string, level: int}>
     */
    public function parseDzsJson(string $json): array
    {
        $payload = json_decode($json, true);
        if (! is_array($payload)) {
            throw new RuntimeException('NKZ JSON nije valjan.');
        }

        $table = $payload['DTable'] ?? $payload;
        if (! is_array($table)) {
            throw new RuntimeException('NKZ dump nema tablicu DTable.');
        }

        $rows = [];
        foreach ($table as $item) {
            if (! is_array($item)) {
                continue;
            }
            $level = (string) ($item['LevelNumber'] ?? '');
            $code = trim((string) ($item['ItemCode'] ?? ''));
            $title = trim((string) ($item['OfficialTitle_HR'] ?? ''));
            if ($level !== '4' || ! preg_match('/^\d{4}$/', $code) || $title === '') {
                continue;
            }
            $rows[$code] = [
                'code' => $code,
                'title' => $title,
                'level' => 4,
            ];
        }

        ksort($rows);

        return array_values($rows);
    }

    /**
     * @return list<array{code: string, title: string, level: int}>
     */
    public function snapshotRows(): array
    {
        $path = self::snapshotPath();
        if (! is_file($path)) {
            throw new RuntimeException('Nedostaje lokalni NKZ-10 šifrarnik: '.$path);
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (! is_array($payload)) {
            throw new RuntimeException('Lokalni NKZ-10 šifrarnik nije valjan JSON.');
        }

        $rows = [];
        foreach ($payload as $item) {
            if (! is_array($item)) {
                continue;
            }
            $code = trim((string) ($item['code'] ?? ''));
            $title = trim((string) ($item['title'] ?? ''));
            if (! preg_match('/^\d{4}$/', $code) || $title === '') {
                continue;
            }
            $rows[] = [
                'code' => $code,
                'title' => $title,
                'level' => (int) ($item['level'] ?? 4),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{code: string, title: string, level: int}>  $rows
     */
    public function sync(array $rows): int
    {
        if ($rows === []) {
            throw new RuntimeException('NKZ-10 šifrarnik je prazan.');
        }

        $now = now();
        DB::transaction(function () use ($rows, $now) {
            NkzOccupation::query()->delete();
            foreach (array_chunk($rows, 100) as $chunk) {
                NkzOccupation::query()->insert(array_map(fn (array $row) => [
                    'code' => $row['code'],
                    'title' => $row['title'],
                    'level' => $row['level'] ?? 4,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }
        });

        NkzOccupation::forgetCatalogCache();

        return count($rows);
    }

    /**
     * @param  list<array{code: string, title: string, level: int}>  $rows
     */
    public function writeSnapshot(array $rows): void
    {
        $dir = dirname(self::snapshotPath());
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents(
            self::snapshotPath(),
            json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n",
        );
    }

    public function fetchDump(): string
    {
        $response = Http::timeout((int) config('nkz.timeout', 90))
            ->withHeaders([
                'User-Agent' => 'SuperSkyCrew-HR/1.0',
                'Accept' => 'application/json,*/*',
            ])
            ->get((string) config('nkz.dump_url'))
            ->throw();

        return $response->body();
    }

    public function seedFromSnapshot(): int
    {
        return $this->sync($this->snapshotRows());
    }
}
