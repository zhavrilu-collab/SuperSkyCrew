<?php

namespace Tests\Feature;

use App\Enums\CompetencyKind;
use App\Enums\FamilyKin;
use App\Enums\InternalActKind;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\OrgSeatStatus;
use App\Enums\PersonStatus;
use App\Models\JobPosition;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrBacklogModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_wave_modules_create_core_records(): void
    {
        [$owner, $organization] = $this->seedOwner();
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
            'first_name' => 'Iva',
            'last_name' => 'Horvat',
            'email' => 'iva@hr.demo',
        ]);
        $job = JobPosition::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Analitičar',
        ]);

        $this->actingAs($owner)
            ->put(route('organization.settings.organization', $organization->slug), [
                'name' => $organization->name,
                'organization_email' => 'info@hr.demo',
                'oib' => '12345678901',
                'organization_type' => 'company',
                'mbs' => '080123456',
                'street' => 'Ilica 1',
                'city' => 'Zagreb',
                'website' => 'https://demo.hr',
                'nkd' => '62.01',
            ])
            ->assertRedirect();
        $this->assertSame('080123456', $organization->fresh()->mbs);

        $this->actingAs($owner)
            ->post(route('organization.family.store', $organization->slug), [
                'person_id' => $person->id,
                'first_name' => 'Lana',
                'last_name' => 'Horvat',
                'kin' => FamilyKin::Child->value,
                'is_dependent' => '1',
            ])
            ->assertRedirect();
        $this->assertSame(1, $person->fresh()->children_count);

        $this->actingAs($owner)
            ->post(route('organization.segments.store', $organization->slug), [
                'name' => 'Maloprodaja',
                'code' => 'MP',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('organization.positions.store', $organization->slug), [
                'job_position_id' => $job->id,
                'status' => OrgSeatStatus::Open->value,
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('organization.competencies.store', $organization->slug), [
                'name' => 'Excel',
                'kind' => CompetencyKind::Hard->value,
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('organization.acts.store', $organization->slug), [
                'title' => 'Pravilnik o radu',
                'kind' => InternalActKind::Rulebook->value,
                'must_read' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('organization.contracts.index', $organization->slug))
            ->assertOk()
            ->assertSee('Registar ugovora');

        $this->actingAs($owner)
            ->get(route('organization.systematization.plan', $organization->slug))
            ->assertOk()
            ->assertSee('Plan radnih pozicija')
            ->assertSee('Analitičar');

        $this->actingAs($owner)
            ->get(route('organization.my-documents.index', $organization->slug))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedOwner(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Backlog d.o.o.',
            'slug' => 'backlog-ui',
            'status' => OrganizationStatus::Active,
            'plan' => 'basic',
            'email' => 'info@hr.demo',
            'oib' => '12345678901',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$user, $organization];
    }
}
