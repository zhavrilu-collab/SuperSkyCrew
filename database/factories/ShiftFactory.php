<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => 'Prva',
            'code' => 'P1',
            'starts_at' => '08:00:00',
            'ends_at' => '16:00:00',
            'break_minutes' => 30,
            'is_night' => false,
        ];
    }
}
