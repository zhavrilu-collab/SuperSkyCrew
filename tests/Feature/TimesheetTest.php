<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use App\Services\ClockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_timesheet_and_day_view(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Babić',
            'status' => PersonStatus::Employee,
        ]);

        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::In->value,
            'occurred_at' => now()->startOfDay()->addHours(8)->toDateTimeString(),
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Zaboravljena prijava',
        ]);
        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::Out->value,
            'occurred_at' => now()->startOfDay()->addHours(16)->toDateTimeString(),
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Zaboravljena odjava',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.timesheet.index', $organization->slug))
            ->assertOk()
            ->assertSee('Šihterica')
            ->assertSee('Iva Babić')
            ->assertSee('8.0');

        $this->actingAs($owner)
            ->get(route('organization.timesheet.day', [
                $organization->slug,
                $person,
                now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Dnevni slog')
            ->assertSee('Ručni unos')
            ->assertSee('8.00');
    }

    public function test_employee_cannot_open_timesheet(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);

        $this->actingAs($employee)
            ->get(route('organization.timesheet.index', $organization->slug))
            ->assertForbidden();
    }

    public function test_manager_can_store_manual_punch_with_reason(): void
    {
        [$manager, $organization] = $this->seedMember(OrganizationRole::Manager);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($manager)
            ->from(route('organization.timesheet.day', [$organization->slug, $person, now()->toDateString()]))
            ->post(route('organization.timesheet.manual', [$organization->slug, $person]), [
                'occurred_at' => now()->startOfDay()->addHours(8)->format('Y-m-d\TH:i'),
                'type' => PunchType::In->value,
                'reason' => 'Zaboravljena prijava na kiosku',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('punches', [
            'person_id' => $person->id,
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Zaboravljena prijava na kiosku',
        ]);
    }

    public function test_accountant_can_view_timesheet_but_not_manual_punch(): void
    {
        [$accountant, $organization] = $this->seedMember(OrganizationRole::Accountant);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Lana',
            'last_name' => 'Matić',
        ]);

        $this->actingAs($accountant)
            ->get(route('organization.timesheet.index', $organization->slug))
            ->assertOk()
            ->assertSee('Lana Matić');

        $this->actingAs($accountant)
            ->get(route('organization.timesheet.day', [$organization->slug, $person, now()->toDateString()]))
            ->assertOk()
            ->assertDontSee('Ručni unos');

        $this->actingAs($accountant)
            ->post(route('organization.timesheet.manual', [$organization->slug, $person]), [
                'occurred_at' => now()->format('Y-m-d\TH:i'),
                'type' => PunchType::In->value,
                'reason' => 'Ne smije',
            ])
            ->assertForbidden();
    }

    public function test_dashboard_shows_people_and_present_counts(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
        ]);

        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::In->value,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('Na poslu')
            ->assertSee('1')
            ->assertSee('Kadar')
            ->assertSee('Šihterica');
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Šihterica d.o.o.',
            'slug' => 'sihterica-firma',
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
