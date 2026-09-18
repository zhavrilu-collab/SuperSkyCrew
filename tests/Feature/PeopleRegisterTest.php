<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_prints_register_as_of_date_and_exports_csv(): void
    {
        [$owner, $organization] = $this->seedOwner();
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Ana',
            'last_name' => 'Aktivna',
            'status' => PersonStatus::Employee,
            'started_at' => '2026-01-15',
            'ended_at' => null,
            'oib' => null,
            'job_title' => 'Referentica',
        ]);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Bivši',
            'last_name' => 'Radnik',
            'status' => PersonStatus::Former,
            'started_at' => '2024-03-01',
            'ended_at' => '2025-12-31',
        ]);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Marta',
            'last_name' => 'Kandidat',
            'status' => PersonStatus::Candidate,
            'started_at' => null,
        ]);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Buduca',
            'last_name' => 'Dolazak',
            'status' => PersonStatus::Employee,
            'started_at' => '2026-12-01',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.book', [$organization->slug, 'na' => '2026-09-18']))
            ->assertOk()
            ->assertSee('Matična knjiga radnika')
            ->assertSee('Aktivna Ana')
            ->assertSee('Referentica')
            ->assertDontSee('Radnik Bivši')
            ->assertDontSee('Kandidat Marta')
            ->assertDontSee('Dolazak Buduca');

        $csv = $this->actingAs($owner)
            ->get(route('organization.people.export', [$organization->slug, 'na' => '2026-09-18']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString('Ana', $csv);
        $this->assertStringContainsString('Aktivna', $csv);
        $this->assertStringContainsString('Referentica', $csv);
        $this->assertStringNotContainsString('Kandidat', $csv);
        $this->assertStringNotContainsString('Bivši', $csv);
    }

    public function test_former_still_counts_as_active_on_last_working_day(): void
    {
        [$owner, $organization] = $this->seedOwner();
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Zadnji',
            'last_name' => 'Dan',
            'status' => PersonStatus::Former,
            'started_at' => '2025-01-01',
            'ended_at' => '2026-09-18',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.book', [$organization->slug, 'na' => '2026-09-18']))
            ->assertOk()
            ->assertSee('Dan Zadnji');

        $this->actingAs($owner)
            ->get(route('organization.people.book', [$organization->slug, 'na' => '2026-09-19']))
            ->assertOk()
            ->assertDontSee('Dan Zadnji');
    }

    public function test_turnover_lists_hires_and_exits(): void
    {
        [$owner, $organization] = $this->seedOwner();
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Novi',
            'last_name' => 'Ulaz',
            'status' => PersonStatus::Employee,
            'started_at' => '2026-03-01',
            'job_title' => 'Knjigovođa',
        ]);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Stari',
            'last_name' => 'Izlaz',
            'status' => PersonStatus::Former,
            'started_at' => '2020-01-01',
            'ended_at' => '2026-06-15',
            'ended_reason' => 'Sporazumni raskid',
        ]);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Van',
            'last_name' => 'Raspona',
            'status' => PersonStatus::Employee,
            'started_at' => '2025-01-01',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.turnover', [
                $organization->slug,
                'from' => '2026-01-01',
                'to' => '2026-09-18',
            ]))
            ->assertOk()
            ->assertSee('Fluktuacija')
            ->assertSee('Novi Ulaz')
            ->assertSee('Knjigovođa')
            ->assertSee('Stari Izlaz')
            ->assertSee('Sporazumni raskid')
            ->assertDontSee('Van Raspona');
    }

    public function test_employee_cannot_open_register_or_turnover(): void
    {
        $employee = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Knjiga d.o.o.',
            'slug' => 'knjiga-radnik-'.uniqid(),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);

        $this->actingAs($employee)
            ->get(route('organization.people.book', $organization->slug))
            ->assertForbidden();
        $this->actingAs($employee)
            ->get(route('organization.people.export', $organization->slug))
            ->assertForbidden();
        $this->actingAs($employee)
            ->get(route('organization.people.turnover', $organization->slug))
            ->assertForbidden();
    }

    public function test_people_index_filters_by_status(): void
    {
        [$owner, $organization] = $this->seedOwner();
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Ana',
            'last_name' => 'Radnica',
            'status' => PersonStatus::Employee,
        ]);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Kandidatkinja',
            'status' => PersonStatus::Candidate,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.index', [$organization->slug, 'status' => PersonStatus::Candidate->value]))
            ->assertOk()
            ->assertSee('Iva Kandidatkinja')
            ->assertDontSee('Ana Radnica');
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedOwner(): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Knjiga d.o.o.',
            'slug' => 'knjiga-firma-'.uniqid(),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$owner, $organization];
    }
}
