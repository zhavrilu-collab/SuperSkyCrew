<?php

namespace Tests\Unit;

use App\Services\NkzOccupationImporter;
use Tests\TestCase;

class NkzOccupationImporterTest extends TestCase
{
    public function test_keeps_only_four_digit_level_four_groups(): void
    {
        $rows = (new NkzOccupationImporter)->parseDzsJson(json_encode([
            'DTable' => [
                ['ItemCode' => '4110', 'OfficialTitle_HR' => 'Uredski službenici', 'LevelNumber' => '4'],
                ['ItemCode' => '1210', 'OfficialTitle_HR' => 'Direktori', 'LevelNumber' => '3'],
                ['ItemCode' => '12', 'OfficialTitle_HR' => 'Menadžeri', 'LevelNumber' => '2'],
                ['ItemCode' => '99999', 'OfficialTitle_HR' => 'Predugo', 'LevelNumber' => '4'],
            ],
        ], JSON_UNESCAPED_UNICODE));

        $this->assertCount(1, $rows);
        $this->assertSame('4110', $rows[0]['code']);
        $this->assertSame('Uredski službenici', $rows[0]['title']);
        $this->assertSame(4, $rows[0]['level']);
    }
}
