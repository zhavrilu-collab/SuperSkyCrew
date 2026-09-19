<?php

namespace Database\Factories;

use App\Models\LegalEntity;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalEntity>
 */
class LegalEntityFactory extends Factory
{
    protected $model = LegalEntity::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => 'Demo d.o.o.',
            'code' => 'SJ'.substr(uniqid(), -4),
            'country' => 'Hrvatska',
            'valid_from' => now()->subYear()->toDateString(),
            'valid_to' => null,
        ];
    }
}
