<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake('hr_HR')->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'organization_type' => OrganizationType::Company,
            'theme_key' => 'tirkizna',
            'email' => fake()->unique()->companyEmail(),
            'oib' => '12345678903',
            'city' => 'Zagreb',
        ];
    }
}
