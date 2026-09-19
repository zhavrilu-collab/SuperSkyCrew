<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use App\Services\HrSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_saves_policy_adds_bracket_and_recalculates(): void
    {
        $this->travelTo('2026-09-19 10:00:00');
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        app(HrSetupService::class)->provision($organization);
        $organization->update(['annual_leave_days_per_child' => 2]);

        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Ivan',
            'last_name' => 'Odmor',
            'status' => PersonStatus::Employee,
            'started_at' => '2026-01-19',
            'prior_service_months' => 36,
            'children_count' => 1,
            'annual_leave_days' => 20,
            'annual_leave_manual' => false,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'vrijeme',
                'section' => 'go-politika',
            ]))
            ->assertOk()
            ->assertSee('Osnovni fond GO')
            ->assertSee('Pragovi staža')
            ->assertSee('Ivan Odmor');

        $this->actingAs($owner)
            ->put(route('organization.settings.leave', $organization->slug), [
                'annual_leave_base_days' => 20,
                'annual_leave_days_per_child' => 2,
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('organization.settings.leave.rules.store', $organization->slug), [
                'min_years' => 3,
                'extra_days' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_tenure_rules', [
            'organization_id' => $organization->id,
            'min_years' => 3,
            'extra_days' => 1,
        ]);

        $this->actingAs($owner)
            ->post(route('organization.settings.leave.recalculate', $organization->slug))
            ->assertRedirect();

        $this->assertSame(23, $person->fresh()->annual_leave_days);

        $this->actingAs($owner)
            ->get(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'zaposlenje']))
            ->assertOk()
            ->assertSee('Politika 2026')
            ->assertSee('Preostalo 23');
    }

    public function test_manual_card_is_skipped_on_recalculate(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $organization->update(['annual_leave_days_per_child' => 2]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
            'children_count' => 2,
            'annual_leave_days' => 28,
            'annual_leave_manual' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('organization.settings.leave.recalculate', $organization->slug))
            ->assertRedirect();

        $this->assertSame(28, $person->fresh()->annual_leave_days);
    }

    public function test_employee_cannot_change_leave_policy(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);

        $this->actingAs($employee)
            ->put(route('organization.settings.leave', $organization->slug), [
                'annual_leave_base_days' => 25,
                'annual_leave_days_per_child' => 3,
            ])
            ->assertForbidden();

        $this->actingAs($employee)
            ->post(route('organization.settings.leave.recalculate', $organization->slug))
            ->assertForbidden();
    }

    public function test_saving_card_without_manual_flag_applies_policy(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $organization->update(['annual_leave_days_per_child' => 2]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Maja',
            'last_name' => 'Fond',
            'status' => PersonStatus::Employee,
            'children_count' => 1,
            'annual_leave_days' => 20,
            'annual_leave_manual' => false,
        ]);

        $this->actingAs($owner)
            ->put(route('organization.people.update', [$organization->slug, $person]), [
                'first_name' => 'Maja',
                'last_name' => 'Fond',
                'status' => PersonStatus::Employee->value,
                'children_count' => 1,
                'return_tab' => 'zaposlenje',
            ])
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'zaposlenje']));

        $this->assertSame(22, $person->fresh()->annual_leave_days);
        $this->assertFalse($person->fresh()->annual_leave_manual);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'GO politika d.o.o.',
            'slug' => 'go-politika-'.uniqid(),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'annual_leave_base_days' => 20,
            'annual_leave_days_per_child' => 0,
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $organization];
    }
}
