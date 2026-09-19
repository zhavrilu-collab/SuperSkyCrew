<?php

namespace Tests\Unit;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\JobPosition;
use App\Models\LeaveBalance;
use App\Models\LeaveTenureRule;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenure_adds_prior_service_and_months_with_employer(): void
    {
        $this->travelTo('2026-09-19 10:00:00');
        [, $person] = $this->seedPerson([
            'started_at' => '2026-01-19',
            'prior_service_months' => 36,
        ]);

        $months = app(LeaveService::class)->tenureMonths($person, 2026);

        $this->assertSame(44, $months);
        $this->assertSame(3, intdiv($months, 12));
    }

    public function test_highest_tenure_bracket_wins_not_sum(): void
    {
        $this->travelTo('2026-09-19 10:00:00');
        [$organization, $person] = $this->seedPerson([
            'started_at' => '2001-01-01',
            'prior_service_months' => 0,
            'annual_leave_manual' => false,
        ]);
        LeaveTenureRule::query()->create([
            'organization_id' => $organization->id,
            'min_years' => 10,
            'extra_days' => 2,
        ]);
        LeaveTenureRule::query()->create([
            'organization_id' => $organization->id,
            'min_years' => 20,
            'extra_days' => 4,
        ]);

        $breakdown = app(LeaveService::class)->breakdown($person->fresh(['organization', 'jobPosition']), 2026);

        $this->assertGreaterThanOrEqual(20, $breakdown['tenure_years']);
        $this->assertSame(4, $breakdown['tenure_extra']);
        $this->assertSame(24, $breakdown['calculated']);
    }

    public function test_children_and_job_position_raise_the_floor(): void
    {
        $this->travelTo('2026-09-19 10:00:00');
        [$organization, $person] = $this->seedPerson([
            'started_at' => '2026-03-01',
            'children_count' => 2,
            'annual_leave_manual' => false,
        ]);
        $organization->update([
            'annual_leave_base_days' => 20,
            'annual_leave_days_per_child' => 2,
        ]);
        $position = JobPosition::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Direktor',
            'annual_leave_days' => 25,
        ]);
        $person->update(['job_position_id' => $position->id]);

        $breakdown = app(LeaveService::class)->breakdown($person->fresh(['organization', 'jobPosition']), 2026);

        $this->assertSame(29, $breakdown['calculated']);
    }

    public function test_manual_override_is_not_recalculated(): void
    {
        [, $person] = $this->seedPerson([
            'annual_leave_days' => 30,
            'annual_leave_manual' => true,
            'children_count' => 3,
        ]);

        $balance = app(LeaveService::class)->applyToPerson($person);

        $this->assertSame(30, $balance?->entitled_days);
        $this->assertSame(30, $person->fresh()->annual_leave_days);
    }

    public function test_unused_previous_year_becomes_carried_old_first(): void
    {
        [, $person] = $this->seedPerson([
            'annual_leave_days' => 20,
            'annual_leave_manual' => true,
        ]);
        LeaveBalance::query()->create([
            'organization_id' => $person->organization_id,
            'person_id' => $person->id,
            'year' => 2025,
            'entitled_days' => 20,
            'carried_days' => 0,
            'used_days' => 15,
        ]);

        $snapshot = app(LeaveService::class)->snapshot($person, 2026);

        $this->assertSame(5, $snapshot['carried']);
        $this->assertSame(5, $snapshot['remaining_old']);
        $this->assertSame(20, $snapshot['remaining_new']);
        $this->assertSame(25, $snapshot['remaining']);
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return array{0: Organization, 1: Person}
     */
    private function seedPerson(array $attrs = []): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'GO servis d.o.o.',
            'slug' => 'go-servis-'.uniqid(),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'annual_leave_base_days' => 20,
            'annual_leave_days_per_child' => 0,
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);
        $person = Person::factory()->create(array_merge([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
            'annual_leave_days' => 20,
            'annual_leave_manual' => false,
        ], $attrs));

        return [$organization, $person];
    }
}
