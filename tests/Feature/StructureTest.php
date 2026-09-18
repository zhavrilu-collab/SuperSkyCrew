<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use App\Services\HrSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_department_and_job_position(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->get(route('organization.structure.index', $organization->slug))
            ->assertOk()
            ->assertSee('Struktura organizacije')
            ->assertSee('Stanje na dan');

        $this->actingAs($owner)
            ->post(route('organization.structure.departments.store', $organization->slug), [
                'name' => 'Operativa',
                'code' => 'OPS',
                'manager_user_id' => $owner->id,
                'valid_from' => '2026-01-01',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('organization.structure.positions.store', $organization->slug), [
                'name' => 'Referent',
                'rad1g' => '4110',
                'annual_leave_days' => 20,
                'valid_from' => '2026-01-01',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', [
            'organization_id' => $organization->id,
            'name' => 'Operativa',
            'code' => 'OPS',
        ]);
        $this->assertDatabaseHas('job_positions', [
            'organization_id' => $organization->id,
            'name' => 'Referent',
            'rad1g' => '4110',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.structure.index', [$organization->slug, 'na' => '2026-09-18']))
            ->assertOk()
            ->assertSee('Operativa')
            ->assertSee('Referent')
            ->assertSee('4110');
    }

    public function test_owner_can_create_cost_center_and_assign_it_on_person_card(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->post(route('organization.structure.cost-centers.store', $organization->slug), [
                'name' => 'Proizvodnja',
                'code' => '300',
                'valid_from' => '2026-01-01',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cost_centers', [
            'organization_id' => $organization->id,
            'name' => 'Proizvodnja',
            'code' => '300',
        ]);

        $costCenter = CostCenter::query()->where('organization_id', $organization->id)->where('code', '300')->first();
        $this->assertNotNull($costCenter);

        CostCenter::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Stari MT',
            'code' => '900',
            'valid_from' => '2024-01-01',
            'valid_to' => '2025-12-31',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.structure.index', [$organization->slug, 'na' => '2026-09-18']))
            ->assertOk()
            ->assertSee('Mjesta troška')
            ->assertSee('Proizvodnja')
            ->assertSee('300')
            ->assertDontSee('Stari MT');

        $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Lana',
                'last_name' => 'Matić',
                'status' => PersonStatus::Employee->value,
                'cost_center_id' => $costCenter->id,
            ])
            ->assertRedirect();

        $person = Person::query()->where('last_name', 'Matić')->first();
        $this->assertNotNull($person);
        $this->assertSame($costCenter->id, $person->cost_center_id);

        $this->actingAs($owner)
            ->get(route('organization.people.index', $organization->slug))
            ->assertOk()
            ->assertSee('300 · Proizvodnja');

        $csv = $this->actingAs($owner)
            ->get(route('organization.people.export', $organization->slug))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('Mjesto troška', $csv);
        $this->assertStringContainsString('300 · Proizvodnja', $csv);

        $this->actingAs($owner)
            ->from(route('organization.structure.index', $organization->slug))
            ->delete(route('organization.structure.cost-centers.destroy', [$organization->slug, $costCenter]))
            ->assertRedirect()
            ->assertSessionHasErrors('cost_center');
        $this->assertDatabaseHas('cost_centers', ['id' => $costCenter->id]);
    }

    public function test_expired_department_is_hidden_on_snapshot_date(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        Department::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Stari odjel',
            'code' => 'OLD',
            'valid_from' => '2024-01-01',
            'valid_to' => '2025-12-31',
        ]);
        Department::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Novi odjel',
            'code' => 'NEW',
            'valid_from' => '2026-01-01',
            'valid_to' => null,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.structure.index', [$organization->slug, 'na' => '2026-09-18']))
            ->assertOk()
            ->assertSee('Novi odjel')
            ->assertDontSee('Stari odjel');

        $this->actingAs($owner)
            ->get(route('organization.structure.index', [$organization->slug, 'na' => '2025-06-01']))
            ->assertOk()
            ->assertSee('Stari odjel')
            ->assertDontSee('>Novi odjel<');
    }

    public function test_person_card_takes_department_and_job_title_from_catalog(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $department = Department::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Računovodstvo',
            'code' => 'RAC',
            'manager_user_id' => $owner->id,
        ]);
        $position = JobPosition::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Knjigovođa',
            'rad1g' => '3313',
        ]);

        $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Ana',
                'last_name' => 'Kovač',
                'status' => PersonStatus::Employee->value,
                'department_id' => $department->id,
                'job_position_id' => $position->id,
            ])
            ->assertRedirect();

        $person = Person::query()->where('last_name', 'Kovač')->first();
        $this->assertNotNull($person);
        $this->assertSame($department->id, $person->department_id);
        $this->assertSame($position->id, $person->job_position_id);
        $this->assertSame('Knjigovođa', $person->job_title);
        $this->assertSame($owner->id, $person->manager_user_id);

        $this->actingAs($owner)
            ->get(route('organization.people.index', $organization->slug))
            ->assertOk()
            ->assertSee('Računovodstvo')
            ->assertSee('Knjigovođa');
    }

    public function test_manager_sees_only_own_department_on_calendar_and_timesheet(): void
    {
        [$owner, $manager, $organization, $own, $other] = $this->seedScopedOrg();

        $this->actingAs($manager)
            ->get(route('organization.absences.calendar', [$organization->slug, 'month' => '2026-09']))
            ->assertOk()
            ->assertSee($own->fullName())
            ->assertDontSee($other->fullName());

        $this->actingAs($owner)
            ->get(route('organization.absences.calendar', [
                $organization->slug,
                'month' => '2026-09',
                'department' => $own->department_id,
            ]))
            ->assertOk()
            ->assertSee($own->fullName())
            ->assertDontSee($other->fullName());

        $this->actingAs($manager)
            ->get(route('organization.timesheet.index', $organization->slug))
            ->assertOk()
            ->assertSee($own->fullName())
            ->assertDontSee($other->fullName());

        $this->actingAs($manager)
            ->get(route('organization.timesheet.day', [$organization->slug, $other, now()->toDateString()]))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('organization.timesheet.manual', [$organization->slug, $other]), [
                'occurred_at' => now()->startOfDay()->addHours(8)->format('Y-m-d\TH:i'),
                'type' => PunchType::In->value,
                'reason' => 'Ne smije',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('organization.timesheet.manual', [$organization->slug, $own]), [
                'occurred_at' => now()->startOfDay()->addHours(8)->format('Y-m-d\TH:i'),
                'type' => PunchType::In->value,
                'reason' => 'Zaboravljena prijava',
                'channel' => ClockChannel::Manager->value,
            ])
            ->assertRedirect();
    }

    public function test_employee_cannot_open_structure_and_department_with_people_cannot_be_deleted(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);
        $department = Department::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Operativa',
            'code' => 'OPS',
        ]);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($employee)
            ->get(route('organization.structure.index', $organization->slug))
            ->assertForbidden();

        $this->actingAs($owner)
            ->from(route('organization.structure.index', $organization->slug))
            ->delete(route('organization.structure.departments.destroy', [$organization->slug, $department]))
            ->assertRedirect()
            ->assertSessionHasErrors('department');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Struktura d.o.o.',
            'slug' => 'struktura-firma-'.uniqid(),
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

    /**
     * @return array{0: User, 1: User, 2: Organization, 3: Person, 4: Person}
     */
    private function seedScopedOrg(): array
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Odjel d.o.o.',
            'slug' => 'odjel-firma-'.uniqid(),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => OrganizationRole::Owner,
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $manager->id,
            'role' => OrganizationRole::Manager,
        ]);

        $ops = Department::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Operativa',
            'code' => 'OPS',
            'manager_user_id' => $manager->id,
        ]);
        $uprava = Department::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Uprava',
            'code' => 'UPR',
            'manager_user_id' => $owner->id,
        ]);

        $own = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Operativa',
            'status' => PersonStatus::Employee,
            'department_id' => $ops->id,
            'manager_user_id' => $manager->id,
        ]);
        $other = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Marta',
            'last_name' => 'Uprava',
            'status' => PersonStatus::Employee,
            'department_id' => $uprava->id,
            'manager_user_id' => $owner->id,
        ]);

        app(HrSetupService::class)->provision($organization);

        return [$owner, $manager, $organization, $own, $other];
    }
}
