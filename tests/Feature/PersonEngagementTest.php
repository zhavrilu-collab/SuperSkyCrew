<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\Department;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\PersonEngagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonEngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_person_opens_engagement_and_department_change_splits_period(): void
    {
        $this->travelTo('2026-06-01 10:00:00');
        [$owner, $organization] = $this->seedOwner();
        $ops = Department::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Operativa',
            'code' => 'OPS',
        ]);
        $uprava = Department::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Uprava',
            'code' => 'UPR',
        ]);

        $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Iva',
                'last_name' => 'Premjestaj',
                'status' => PersonStatus::Employee->value,
                'department_id' => $ops->id,
                'job_title' => 'Referentica',
                'started_at' => '2026-03-01',
            ])
            ->assertRedirect();

        $person = Person::query()->where('last_name', 'Premjestaj')->first();
        $this->assertNotNull($person);
        $this->assertSame(1, $person->engagements()->count());
        $this->assertSame($ops->id, $person->engagements()->first()->department_id);

        $this->travelTo('2026-09-18 10:00:00');
        $this->actingAs($owner)
            ->put(route('organization.people.update', [$organization->slug, $person]), [
                'first_name' => 'Iva',
                'last_name' => 'Premjestaj',
                'status' => PersonStatus::Employee->value,
                'department_id' => $uprava->id,
                'job_title' => 'Referentica',
                'started_at' => '2026-03-01',
                'return_tab' => 'angazman',
            ])
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'angazman']));

        $person->refresh();
        $this->assertSame(2, $person->engagements()->count());

        $this->actingAs($owner)
            ->get(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'angazman']))
            ->assertOk()
            ->assertSee('Povijest angažmana')
            ->assertSee('Operativa')
            ->assertSee('Uprava')
            ->assertSee('važeće');

        $this->actingAs($owner)
            ->get(route('organization.people.book', [$organization->slug, 'na' => '2026-05-01']))
            ->assertOk()
            ->assertSee('Operativa')
            ->assertDontSee('Uprava');

        $this->actingAs($owner)
            ->get(route('organization.people.book', [$organization->slug, 'na' => '2026-09-18']))
            ->assertOk()
            ->assertSee('Uprava');

        $csv = $this->actingAs($owner)
            ->get(route('organization.people.export', [$organization->slug, 'na' => '2026-05-01']))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('Operativa', $csv);
        $this->assertStringNotContainsString('Uprava', $csv);
    }

    public function test_hire_updates_engagement_status(): void
    {
        [$owner, $organization] = $this->seedOwner();
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Lana',
            'last_name' => 'Kandidat',
            'status' => PersonStatus::Candidate,
            'started_at' => null,
            'job_title' => 'Pripravnica',
        ]);

        $this->assertSame(PersonStatus::Candidate, $person->engagements()->first()?->status);

        $this->actingAs($owner)
            ->post(route('organization.people.hire', [$organization->slug, $person]))
            ->assertRedirect();

        $this->assertSame(PersonStatus::Employee, $person->fresh()->engagements()->orderByDesc('id')->first()?->status);
    }

    public function test_same_day_change_mutates_open_row(): void
    {
        $this->travelTo('2026-09-18 09:00:00');
        [$owner, $organization] = $this->seedOwner();
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'started_at' => '2026-09-18',
            'job_title' => 'Prvo',
        ]);
        $this->assertSame(1, PersonEngagement::query()->where('person_id', $person->id)->count());

        $person->update(['job_title' => 'Drugo']);
        $this->assertSame(1, PersonEngagement::query()->where('person_id', $person->id)->count());
        $this->assertSame('Drugo', $person->engagements()->first()->job_title);
    }

    public function test_employee_cannot_open_engagement_tab(): void
    {
        [$owner, $organization] = $this->seedOwner();
        $person = Person::factory()->create(['organization_id' => $organization->id]);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);

        $this->actingAs($employee)
            ->get(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'angazman']))
            ->assertForbidden();
        $this->assertNotNull($owner->id);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedOwner(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Angažman d.o.o.',
            'slug' => 'angazman-'.uniqid(),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$user, $organization];
    }
}
