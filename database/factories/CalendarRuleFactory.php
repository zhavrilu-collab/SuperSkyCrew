<?php

namespace Database\Factories;

use App\Enums\CalendarLevel;
use App\Models\CalendarRule;
use App\Models\Organization;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarRule>
 */
class CalendarRuleFactory extends Factory
{
    protected $model = CalendarRule::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'level' => CalendarLevel::Organization,
            'shift_id' => Shift::factory(),
            'weekday' => 1,
            'valid_from' => now()->subYear()->toDateString(),
            'valid_to' => null,
        ];
    }
}
