<?php

namespace Database\Factories;

use App\Models\EnterpriseUnit;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnterpriseUnit>
 */
class EnterpriseUnitFactory extends Factory
{
    protected $model = EnterpriseUnit::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => 'Sjedište',
            'valid_from' => now()->subYear()->toDateString(),
            'valid_to' => null,
        ];
    }
}
