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
            ->assertSee('Nadzornici', false)
            ->assertSee('Kompetencije')
            ->assertSee('ustroj-pilula', false)
            ->assertSee('Akti');

        $this->actingAs($owner)
            ->get(route('organization.systematization.competencies', $organization->slug))
            ->assertRedirect(route('organization.systematization.index', $organization->slug));

        $this->actingAs($owner)
            ->get(route('organization.systematization.acts', $organization->slug))
            ->assertRedirect(route('organization.systematization.index', [
                'slug' => $organization->slug,
                'pogled' => 'akti',
            ]));

        $this->actingAs($owner)
            ->get(route('organization.systematization.index', [
                'slug' => $organization->slug,
                'pogled' => 'akti',
            ]))
            ->assertOk()
            ->assertSee('Interni akti')
            ->assertSee('Spremi akt');
    }

    public function test_owner_binds_competency_on_selected_job(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $job = JobPosition::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Voditelj pogona',
            'rad1g' => '3122',
        ]);

        $this->actingAs($owner)
            ->post(route('organization.competencies.store', $organization->slug), [
                'name' => 'Excel',
                'kind' => \App\Enums\CompetencyKind::Hard->value,
                'mjesto' => $job->id,
            ])
            ->assertRedirect(route('organization.systematization.index', [
                'slug' => $organization->slug,
                'mjesto' => $job->id,
            ]));

        $competency = \App\Models\Competency::query()->where('organization_id', $organization->id)->where('name', 'Excel')->first();
        $this->assertNotNull($competency);

        $this->actingAs($owner)
            ->post(route('organization.competencies.attach', $organization->slug), [
                'job_position_id' => $job->id,
                'competency_id' => $competency->id,
                'required_level' => 4,
            ])
            ->assertRedirect(route('organization.systematization.index', [
                'slug' => $organization->slug,
                'mjesto' => $job->id,
            ]));

        $this->actingAs($owner)
            ->get(route('organization.systematization.index', [
                'slug' => $organization->slug,
                'mjesto' => $job->id,
            ]))
            ->assertOk()
            ->assertSee('Excel')
            ->assertSee('Tvrda vještina');
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
