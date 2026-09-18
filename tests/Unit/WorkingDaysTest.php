<?php

namespace Tests\Unit;

use App\Support\WorkingDays;
use Carbon\Carbon;
use Tests\TestCase;

class WorkingDaysTest extends TestCase
{
    public function test_skips_weekend_and_fixed_holiday(): void
    {
        $dates = WorkingDays::dates(
            Carbon::parse('2026-05-01', 'Europe/Zagreb'),
            Carbon::parse('2026-05-04', 'Europe/Zagreb'),
        );

        $this->assertSame(['2026-05-04'], $dates);
    }
}
