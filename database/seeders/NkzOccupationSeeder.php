<?php

namespace Database\Seeders;

use App\Services\NkzOccupationImporter;
use Illuminate\Database\Seeder;

class NkzOccupationSeeder extends Seeder
{
    public function run(): void
    {
        app(NkzOccupationImporter::class)->seedFromSnapshot();
    }
}
