<?php

namespace App\Console\Commands;

use App\Services\NkzOccupationImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportNkzOccupationsCommand extends Command
{
    protected $signature = 'nkz:import
        {--file= : Lokalni DZS JSON umjesto preuzimanja}
        {--snapshot : Spremi uvožene skupine u database/data/nkz10-rad1g.json}';

    protected $description = 'Uvezi NKZ-10 skupine (razina 4) za RAD-1G iz DZS KLASUS dumpa.';

    public function handle(NkzOccupationImporter $importer): int
    {
        try {
            $rows = $this->rows($importer);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $count = $importer->sync($rows);
        if ($this->option('snapshot')) {
            $importer->writeSnapshot($rows);
            $this->info('Spremljen lokalni šifrarnik: '.NkzOccupationImporter::snapshotPath());
        }

        $this->info('Uvezeno '.$count.' NKZ-10 skupina za RAD-1G.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{code: string, title: string, level: int}>
     */
    private function rows(NkzOccupationImporter $importer): array
    {
        $file = $this->option('file');
        if (is_string($file) && $file !== '') {
            $path = $file;
            if (! is_file($path)) {
                $path = base_path($file);
            }
            if (! is_file($path)) {
                throw new \RuntimeException('Datoteka nije pronađena: '.$file);
            }

            return $importer->parseDzsJson((string) file_get_contents($path));
        }

        try {
            return $importer->parseDzsJson($importer->fetchDump());
        } catch (Throwable $exception) {
            $this->warn('DZS dump nije dostupan ('.$exception->getMessage().'). Uvozim lokalni šifrarnik.');

            return $importer->snapshotRows();
        }
    }
}
