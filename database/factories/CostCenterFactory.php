<?php

namespace Database\Factories;

use App\Models\CostCenter;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostCenter>
 */
class CostCenterFactory extends Factory
{
    protected $model = CostCenter::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => 'MT'.substr(uniqid(), -5),
            'name' => 'Opći trošak',
            'valid_from' => now()->subYear()->toDateString(),
            'valid_to' => null,
        ];
    }
}
