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
            ->assertSee('8.00')
            ->assertSee('Evidencijski sati');
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
            ->assertDontSee('Ručni unos')
            ->assertDontSee('Spremi evidenciju');

        $this->actingAs($accountant)
            ->post(route('organization.timesheet.manual', [$organization->slug, $person]), [
                'occurred_at' => now()->format('Y-m-d\TH:i'),
                'type' => PunchType::In->value,
                'reason' => 'Ne smije',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_correct_evidential_hours_for_payroll(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $costCenter = \App\Models\CostCenter::factory()->create([
            'organization_id' => $organization->id,
            'code' => '200',
            'name' => 'Operativa',
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Babić',
            'status' => PersonStatus::Employee,
            'cost_center_id' => $costCenter->id,
        ]);
        app(\App\Services\HrSetupService::class)->provision($organization);

        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::In->value,
            'occurred_at' => now()->startOfDay()->addHours(8)->toDateTimeString(),
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Prijava',
        ]);
        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::Out->value,
            'occurred_at' => now()->startOfDay()->addHours(16)->toDateTimeString(),
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Odjava',
        ]);

        $this->actingAs($owner)
            ->post(route('organization.timesheet.evidential', [$organization->slug, $person, now()->toDateString()]), [
                'evidential_minutes' => 450,
                'evidential_code' => 'RD',
                'evidential_cost_center_id' => $costCenter->id,
                'evidential_note' => 'Zaokruživanje na 7.5 h',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'person_id' => $person->id,
            'evidential_minutes' => 450,
            'evidential_code' => 'RD',
            'evidential_manual' => 1,
        ]);

        $csv = $this->actingAs($owner)
            ->get(route('organization.timesheet.export', [
                'slug' => $organization->slug,
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('Šifra', $csv);
        $this->assertStringContainsString('Mjesto troška', $csv);
        $this->assertStringContainsString('RD', $csv);
        $this->assertStringContainsString('200 · Operativa', $csv);
        $this->assertStringContainsString('450', $csv);

        $this->actingAs($owner)
            ->post(route('organization.timesheet.evidential', [$organization->slug, $person, now()->toDateString()]), [
                'evidential_minutes' => 420,
                'evidential_code' => 'RD',
                'evidential_note' => 'Bez odabranog MT — ostaje osobno',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'person_id' => $person->id,
            'evidential_minutes' => 420,
            'evidential_cost_center_id' => $costCenter->id,
        ]);
    }

    public function test_manager_cannot_correct_evidential_hours(): void
    {
        [$manager, $organization] = $this->seedMember(OrganizationRole::Manager);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);
        app(\App\Services\HrSetupService::class)->provision($organization);

        $this->actingAs($manager)
            ->post(route('organization.timesheet.evidential', [$organization->slug, $person, now()->toDateString()]), [
                'evidential_minutes' => 480,
                'evidential_code' => 'RD',
                'evidential_note' => 'Ne smije',
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
            ->assertSee('Kadrovi')
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
