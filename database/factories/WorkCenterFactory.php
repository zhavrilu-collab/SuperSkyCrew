<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\WorkCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkCenter>
 */
class WorkCenterFactory extends Factory
{
    protected $model = WorkCenter::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => 'Sjedište',
            'code' => 'POS'.substr(uniqid(), -4),
            'city' => 'Zagreb',
            'valid_from' => now()->subYear()->toDateString(),
            'valid_to' => null,
        ];
    }
}
