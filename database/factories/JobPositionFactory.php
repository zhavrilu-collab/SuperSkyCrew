<?php

namespace Database\Factories;

use App\Models\JobPosition;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosition>
 */
class JobPositionFactory extends Factory
{
    protected $model = JobPosition::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => 'Referent',
            'rad1g' => '4110',
            'annual_leave_days' => 20,
            'valid_from' => now()->subYear()->toDateString(),
            'valid_to' => null,
        ];
    }
}
