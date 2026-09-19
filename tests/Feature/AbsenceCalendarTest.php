<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\RequestType;
use App\Enums\TimeEntryStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\HrSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AbsenceCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_approved_and_pending_leave_on_calendar(): void
    {
        Notification::fake();
        [$owner, $worker, $organization, $person] = $this->seedOrgWithWorker();

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::LeaveAnnual->value,
                'from' => '2026-09-21',
                'to' => '2026-09-22',
                'note' => 'Obitelj',
            ])
            ->assertRedirect();

        $zahtjev = \App\Models\WorkflowRequest::query()->first();

        $this->actingAs($owner)
            ->get(route('organization.absences.calendar', [$organization->slug, 'month' => '2026-09']))
            ->assertOk()
            ->assertSee('Kalendar odsutnosti')
            ->assertSee('rujan 2026.')
            ->assertSee($person->fullName())
            ->assertSee(route('organization.requests.show', [$organization->slug, $zahtjev]));
        $this->actingAs($owner)
            ->post(route('organization.approvals.approve', [$organization->slug, $zahtjev]));

        $this->actingAs($owner)
            ->get(route('organization.absences.calendar', [$organization->slug, 'month' => '2026-09', 'code' => 'GO']))
            ->assertOk()
            ->assertSee($person->fullName())
            ->assertSee('GO');

        $this->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('Odsutni danas');
    }

    public function test_employee_sees_only_own_row(): void
    {
        Notification::fake();
        [$owner, $worker, $organization, $person] = $this->seedOrgWithWorker();
        $other = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Tajna',
            'last_name' => 'Kolegica',
            'status' => PersonStatus::Employee,
        ]);
        TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $other->id,
            'work_date' => '2026-09-21',
            'absence_code' => 'BO',
            'absence_minutes' => 480,
            'status' => TimeEntryStatus::Complete,
        ]);

        $this->actingAs($worker)
            ->get(route('organization.absences.calendar', [$organization->slug, 'month' => '2026-09']))
            ->assertOk()
            ->assertSee($person->fullName())
            ->assertDontSee('Tajna Kolegica');

        $this->actingAs($owner)
            ->get(route('organization.absences.calendar', [$organization->slug, 'month' => '2026-09']))
            ->assertOk()
            ->assertSee('Tajna Kolegica')
            ->assertSee('BO');
    }

    public function test_dashboard_counts_absences_today(): void
    {
        [$owner, $organization] = $this->seedOwner();
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Danas',
            'last_name' => 'Odsutan',
            'status' => PersonStatus::Employee,
        ]);
        TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'work_date' => now()->toDateString(),
            'absence_code' => 'GO',
            'absence_minutes' => 480,
            'status' => TimeEntryStatus::Complete,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('Odsutni danas');

        $this->actingAs($owner)
            ->get(route('organization.absences.calendar', [$organization->slug, 'month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Danas Odsutan');
    }

    /**
     * @return array{0: User, 1: User, 2: Organization, 3: Person}
     */
    private function seedOrgWithWorker(): array
    {
        $owner = User::factory()->create();
        $worker = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Kalendar d.o.o.',
            'slug' => 'kalendar-firma-'.uniqid(),
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
            'user_id' => $worker->id,
            'role' => OrganizationRole::Employee,
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $worker->id,
            'first_name' => 'Ivan',
            'last_name' => 'Kalendar',
            'status' => PersonStatus::Employee,
            'manager_user_id' => $owner->id,
            'annual_leave_days' => 20,
        ]);
        app(HrSetupService::class)->provision($organization);

        return [$owner, $worker, $organization, $person];
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedOwner(): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Kalendar d.o.o.',
            'slug' => 'kalendar-dash-'.uniqid(),
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
