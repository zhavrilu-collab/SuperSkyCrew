<?php

namespace Database\Factories;

use App\Enums\ContractType;
use App\Enums\PersonStatus;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'first_name' => fake('hr_HR')->firstName(),
            'last_name' => fake('hr_HR')->lastName(),
            'oib' => null,
            'job_title' => 'Referent',
            'contract_type' => ContractType::Indefinite,
            'status' => PersonStatus::Employee,
            'started_at' => now()->subMonths(3)->toDateString(),
            'citizenship' => 'HR',
            'annual_leave_days' => 20,
            'annual_leave_manual' => true,
        ];
    }
}
