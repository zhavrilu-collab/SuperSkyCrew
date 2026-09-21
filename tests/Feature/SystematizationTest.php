<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\JobPosition;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystematizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_job_systematization(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        JobPosition::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Voditelj pogona',
            'rad1g' => '3122',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.systematization.index', $organization->slug))
            ->assertOk()
            ->assertSee('Opisi radnih mjesta')
            ->assertSee('Voditelj pogona')
            ->assertSee('3122')
            ->assertSee('Nadzornici', false);
    }

    public function test_employee_cannot_open_systematization(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);

        $this->actingAs($employee)
            ->get(route('organization.systematization.index', $organization->slug))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Sistem d.o.o.',
            'slug' => 'sistem-ui',
            'status' => OrganizationStatus::Active,
            'plan' => 'basic',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $organization];
    }
}
