<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\StaffInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_team_page(): void
    {
        [$owner, $organization] = $this->seedOrganizationWithOwner();

        $this->actingAs($owner)
            ->get(route('organization.team.index', $organization->slug))
            ->assertOk()
            ->assertSee('Tim');
    }

    public function test_employee_cannot_manage_team(): void
    {
        [$owner, $organization] = $this->seedOrganizationWithOwner();

        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);

        $this->actingAs($employee)
            ->get(route('organization.team.index', $organization->slug))
            ->assertForbidden();
    }

    public function test_owner_can_create_staff_invite(): void
    {
        [$owner, $organization] = $this->seedOrganizationWithOwner();

        $this->actingAs($owner)
            ->post(route('organization.team.invite', $organization->slug), [
                'email' => 'racunovodja@firma.hr',
                'role' => OrganizationRole::Accountant->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('staff_invites', [
            'organization_id' => $organization->id,
            'email' => 'racunovodja@firma.hr',
            'role' => OrganizationRole::Accountant->value,
        ]);
    }

    public function test_guest_can_accept_staff_invite(): void
    {
        [$owner, $organization] = $this->seedOrganizationWithOwner();

        $invite = StaffInvite::issue(
            $organization,
            'novi@firma.hr',
            OrganizationRole::Manager,
            $owner->id,
        );

        $this->post(route('staff-invite.store', $invite->token), [
            'name' => 'Novi Član',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertRedirect(route('organization.dashboard', $organization->slug));

        $user = User::query()->where('email', 'novi@firma.hr')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Manager->value,
        ]);

        $invite->refresh();
        $this->assertNotNull($invite->accepted_at);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedOrganizationWithOwner(): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Tim d.o.o.',
            'slug' => 'tim-firma',
            'status' => OrganizationStatus::Active,
            'plan' => 'basic',
        ]);

        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$owner, $organization];
    }
}
