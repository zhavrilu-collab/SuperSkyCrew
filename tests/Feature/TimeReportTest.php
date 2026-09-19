<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\EmploymentInstrument;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Enums\TimeEntryStatus;
use App\Models\CostCenter;
use App\Models\EmploymentContract;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ClockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_monthly_fund_and_csv(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Babić',
            'status' => PersonStatus::Employee,
        ]);
        TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'work_date' => now()->startOfMonth()->toDateString(),
            'total_minutes' => 480,
            'evidential_minutes' => 480,
            'status' => TimeEntryStatus::Complete,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.timesheet.fund', [
                $organization->slug,
                'mjesec' => now()->format('Y-m'),
            ]))
            ->assertOk()
            ->assertSee('Mjesečni fond sati')
            ->assertSee('Iva Babić')
            ->assertSee('40 h');

        $csv = $this->actingAs($owner)
            ->get(route('organization.timesheet.fund-export', [
                $organization->slug,
                'mjesec' => now()->format('Y-m'),
            ]));
        $csv->assertOk();
        $body = $csv->streamedContent();
        $this->assertStringContainsString('Odstupanje min', $body);
        $this->assertStringContainsString('Iva Babić', $body);
        $this->assertDatabaseHas('compliance_exports', [
            'organization_id' => $organization->id,
            'kind' => 'fund',
        ]);
    }

    public function test_part_time_contract_changes_daily_fund(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Lana',
            'last_name' => 'Kratka',
            'status' => PersonStatus::Employee,
        ]);
        EmploymentContract::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'kind' => EmploymentInstrument::EmploymentContract,
            'signed_at' => now()->subYear()->toDateString(),
            'starts_at' => now()->subYear()->toDateString(),
            'weekly_hours' => 20,
            'is_current' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.timesheet.fund', $organization->slug))
            ->assertOk()
            ->assertSee('Lana Kratka')
            ->assertSee('20 h')
            ->assertSee('4.0 h');
    }

    public function test_payroll_hours_aggregate_code_and_cost_center(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $costCenter = CostCenter::factory()->create([
            'organization_id' => $organization->id,
            'code' => '200',
            'name' => 'Operativa',
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Babić',
            'status' => PersonStatus::Employee,
            'oib' => '12345678901',
            'cost_center_id' => $costCenter->id,
        ]);
        foreach ([now()->startOfMonth(), now()->startOfMonth()->addDay()] as $day) {
            TimeEntry::query()->create([
                'organization_id' => $organization->id,
                'person_id' => $person->id,
                'work_date' => $day->toDateString(),
                'total_minutes' => 450,
                'evidential_minutes' => 450,
                'evidential_code' => 'RD',
                'evidential_cost_center_id' => $costCenter->id,
                'status' => TimeEntryStatus::Complete,
            ]);
        }

        $from = now()->startOfMonth()->toDateString();
        $to = now()->startOfMonth()->addDay()->toDateString();

        $this->actingAs($owner)
            ->get(route('organization.timesheet.payroll-hours', [
                $organization->slug,
                'from' => $from,
                'to' => $to,
            ]))
            ->assertOk()
            ->assertSee('Sati za plaće')
            ->assertSee('Iva Babić')
            ->assertSee('200 · Operativa')
            ->assertSee('900')
            ->assertSee('15.00');

        $csv = $this->actingAs($owner)
            ->get(route('organization.timesheet.payroll-hours-export', [
                $organization->slug,
                'from' => $from,
                'to' => $to,
            ]));
        $csv->assertOk();
        $this->assertStringContainsString('Šifra', $csv->streamedContent());
        $this->assertStringContainsString('900', $csv->streamedContent());

        $this->actingAs($owner)
            ->getJson(route('organization.payroll.hours.api', [
                $organization->slug,
                'from' => $from,
                'to' => $to,
            ]))
            ->assertOk()
            ->assertJsonPath('rows.0.code', 'RD')
            ->assertJsonPath('rows.0.minutes', 900)
            ->assertJsonPath('rows.0.hours', 15);
    }

    public function test_manager_can_open_fund_but_not_payroll_hours(): void
    {
        [$manager, $organization] = $this->seedMember(OrganizationRole::Manager);

        $this->actingAs($manager)
            ->get(route('organization.timesheet.fund', $organization->slug))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('organization.timesheet.payroll-hours', $organization->slug))
            ->assertForbidden();
    }

    public function test_employee_cannot_open_fund_or_payroll_hours(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);

        $this->actingAs($employee)
            ->get(route('organization.timesheet.fund', $organization->slug))
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('organization.timesheet.payroll-hours', $organization->slug))
            ->assertForbidden();
    }

    public function test_dashboard_lists_people_currently_at_work(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'first_name' => 'Marta',
            'last_name' => 'Vlasnik',
            'status' => PersonStatus::Employee,
        ]);

        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Web->value,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('Trenutno na poslu')
            ->assertSee('Marta Vlasnik')
            ->assertSee('Prijava');
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Fond d.o.o.',
            'slug' => 'fond-firma-'.uniqid(),
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
