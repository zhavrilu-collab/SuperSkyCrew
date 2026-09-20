<?php

namespace Tests\Feature;

use App\Enums\ContractType;
use App\Enums\EmploymentInstrument;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_and_list_a_person(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->get(route('organization.people.index', $organization->slug))
            ->assertOk()
            ->assertSee('Dosjei zaposlenika')
            ->assertSee('Nema unesenih osoba.');

        $response = $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Ana',
                'last_name' => 'Kovač',
                'status' => PersonStatus::Employee->value,
                'contract_type' => ContractType::Indefinite->value,
                'job_title' => 'Knjigovođa',
                'started_at' => '2026-01-15',
                'citizenship' => 'HR',
            ]);
        $person = Person::query()->where('last_name', 'Kovač')->first();
        $this->assertNotNull($person);
        $response->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'pregled']));

        $this->assertDatabaseHas('people', [
            'organization_id' => $organization->id,
            'first_name' => 'Ana',
            'last_name' => 'Kovač',
            'status' => PersonStatus::Employee->value,
            'job_title' => 'Knjigovođa',
        ]);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/', (string) $person->clock_qr);

        $this->actingAs($owner)
            ->get(route('organization.people.index', $organization->slug))
            ->assertOk()
            ->assertSee('Ana Kovač')
            ->assertSee('Knjigovođa');
    }

    public function test_employee_cannot_access_people_directory(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);

        $this->actingAs($employee)
            ->get(route('organization.people.index', $organization->slug))
            ->assertForbidden();
    }

    public function test_person_can_be_linked_to_organization_user(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $worker = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $worker->id,
            'role' => OrganizationRole::Employee,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Ivan',
                'last_name' => 'Horvat',
                'status' => PersonStatus::Employee->value,
                'user_id' => $worker->id,
            ]);
        $person = Person::query()->where('user_id', $worker->id)->first();
        $this->assertNotNull($person);
        $response->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'pregled']));
        $this->assertNotNull($person);
        $this->assertSame($organization->id, $person->organization_id);
    }

    public function test_owner_can_hire_candidate_into_staff(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $candidate = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Marta',
            'last_name' => 'Kandidat',
            'status' => PersonStatus::Candidate,
            'started_at' => null,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.edit', [$organization->slug, $candidate]))
            ->assertOk()
            ->assertSee('Prenesi u kadar');

        $this->actingAs($owner)
            ->post(route('organization.people.hire', [$organization->slug, $candidate]))
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $candidate]));

        $candidate->refresh();
        $this->assertSame(PersonStatus::Employee, $candidate->status);
        $this->assertNotNull($candidate->started_at);
        $this->assertSame(now()->toDateString(), $candidate->started_at->toDateString());
        $this->assertDatabaseHas('employment_contracts', [
            'person_id' => $candidate->id,
            'kind' => EmploymentInstrument::EmploymentContract->value,
            'is_current' => 1,
        ]);
    }

    public function test_employee_cannot_hire_candidate(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);
        $candidate = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Candidate,
        ]);

        $this->actingAs($employee)
            ->post(route('organization.people.hire', [$organization->slug, $candidate]))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Kadar d.o.o.',
            'slug' => 'kadar-firma',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);

        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $organization];
    }
}
