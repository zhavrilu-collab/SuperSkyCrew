<?php

namespace Tests\Feature;

use App\Models\NkzOccupation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NkzImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_command_loads_level_four_groups_from_dzs_dump(): void
    {
        Http::fake([
            'http://web.dzs.hr/*' => Http::response([
                'DTable' => [
                    ['ItemCode' => '1120', 'OfficialTitle_HR' => 'Glavni i izvršni direktori', 'LevelNumber' => '4'],
                    ['ItemCode' => '121', 'OfficialTitle_HR' => 'Direktori poslovnih jedinica', 'LevelNumber' => '3'],
                ],
            ]),
        ]);

        $this->artisan('nkz:import')
            ->expectsOutputToContain('Uvezeno 1 NKZ-10 skupina')
            ->assertSuccessful();

        $this->assertDatabaseHas('nkz_occupations', [
            'code' => '1120',
            'title' => 'Glavni i izvršni direktori',
        ]);
        $this->assertDatabaseMissing('nkz_occupations', ['code' => '4110']);
        $this->assertSame(1, NkzOccupation::query()->count());
    }

    public function test_migration_snapshot_contains_rad1g_unit_groups(): void
    {
        $this->assertGreaterThanOrEqual(400, NkzOccupation::query()->count());
        $this->assertDatabaseHas('nkz_occupations', ['code' => '4110']);
        $this->assertDatabaseHas('nkz_occupations', ['code' => '1120']);
        $this->assertDatabaseMissing('nkz_occupations', ['code' => '1210']);
    }
}
