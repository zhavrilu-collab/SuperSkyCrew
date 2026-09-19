<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\ContractType;
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

class ExpiryAndWeekTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_expiries_within_thirty_days(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Ivan',
            'last_name' => 'Horvat',
            'status' => PersonStatus::Employee,
            'medical_expires_at' => now()->addDays(10)->toDateString(),
            'work_permit_expires_at' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('Isteci')
            ->assertSee('2');

        $this->actingAs($owner)
            ->get(route('organization.expiries.index', $organization->slug))
            ->assertOk()
            ->assertSee('Ivan Horvat')
            ->assertSee('Liječnički pregled')
            ->assertSee('Dozvola boravka/rada')
            ->assertSee('isteklo');

        $this->actingAs($owner)
            ->get(route('organization.people.index', $organization->slug))
            ->assertOk()
            ->assertSee('istek 2');
    }

    public function test_former_and_far_dates_are_ignored(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Former,
            'medical_expires_at' => now()->addDays(3)->toDateString(),
        ]);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
            'certificate_expires_at' => now()->addDays(60)->toDateString(),
            'contract_type' => ContractType::FixedTerm,
            'ended_at' => now()->addDays(40)->toDateString(),
        ]);

        $this->actingAs($owner)
            ->get(route('organization.expiries.index', $organization->slug))
            ->assertOk()
            ->assertSee('Nema isteka');
    }

    public function test_employee_cannot_open_expiry_list(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);

        $this->actingAs($employee)
            ->get(route('organization.expiries.index', $organization->slug))
            ->assertForbidden();
    }

    public function test_employee_can_open_own_week_and_day_but_not_others(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);
        $own = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'first_name' => 'Ana',
            'last_name' => 'Kovač',
        ]);
        $other = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Marta',
            'last_name' => 'Babić',
        ]);

        app(ClockService::class)->punch($own, $employee, [
            'type' => PunchType::In->value,
            'occurred_at' => now()->startOfDay()->addHours(8)->toDateTimeString(),
            'channel' => ClockChannel::Web->value,
        ]);
        app(ClockService::class)->punch($own, $employee, [
            'type' => PunchType::Out->value,
            'occurred_at' => now()->startOfDay()->addHours(16)->toDateTimeString(),
            'channel' => ClockChannel::Web->value,
        ]);

        $this->actingAs($employee)
            ->get(route('organization.timesheet.index', $organization->slug))
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('organization.timesheet.mine', $organization->slug))
            ->assertOk()
            ->assertSee('Moj tjedan')
            ->assertSee('Ana Kovač')
            ->assertSee('8.0');

        $this->actingAs($employee)
            ->get(route('organization.timesheet.mine', [$organization->slug, 'ispis' => 1]))
            ->assertOk()
            ->assertSee('Uvid u evidenciju radnog vremena')
            ->assertSee('Čl. 20.')
            ->assertSee('Ana Kovač')
            ->assertDontSee('Spremi punch');

        $this->actingAs($employee)
            ->get(route('organization.timesheet.day', [$organization->slug, $own, now()->toDateString()]))
            ->assertOk()
            ->assertSee('Dnevni slog')
            ->assertDontSee('Spremi punch')
            ->assertSee('Moj tjedan');

        $this->actingAs($employee)
            ->get(route('organization.timesheet.day', [$organization->slug, $other, now()->toDateString()]))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Isteci d.o.o.',
            'slug' => 'isteci-firma',
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
