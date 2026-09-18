<?php

namespace Tests\Feature;

use App\Enums\CalendarLevel;
use App\Enums\ClockChannel;
use App\Enums\ExceptionCode;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Models\CalendarRule;
use App\Models\Department;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\Shift;
use App\Models\User;
use App\Services\ClockService;
use App\Services\ShiftResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_shift_and_organization_rule(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->get(route('organization.schedule.index', $organization->slug))
            ->assertOk()
            ->assertSee('Raspored i smjene');

        $this->actingAs($owner)
            ->post(route('organization.schedule.shifts.store', $organization->slug), [
                'name' => 'Prva',
                'code' => 'P1',
                'starts_at' => '08:00',
                'ends_at' => '16:00',
                'break_minutes' => 30,
            ])
            ->assertRedirect();

        $shift = Shift::query()->first();
        $this->assertNotNull($shift);
        $this->assertStringStartsWith('08:00', (string) $shift->starts_at);

        $this->actingAs($owner)
            ->post(route('organization.schedule.rules.store', $organization->slug), [
                'level' => CalendarLevel::Organization->value,
                'weekday' => 5,
                'shift_id' => $shift->id,
                'valid_from' => '2026-01-01',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('organization.schedule.index', [$organization->slug, 'from' => '2026-09-14']))
            ->assertOk()
            ->assertSee('P1')
            ->assertSee('Organizacija');
    }

    public function test_person_rule_wins_over_organization_and_holiday_clears_default(): void
    {
        [$owner, $organization, $person] = $this->seedPerson();
        $first = Shift::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Prva',
            'code' => 'P1',
            'starts_at' => '08:00:00',
            'ends_at' => '16:00:00',
        ]);
        $second = Shift::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Druga',
            'code' => 'P2',
            'starts_at' => '14:00:00',
            'ends_at' => '22:00:00',
        ]);
        foreach ([1, 2, 3, 4, 5] as $weekday) {
            CalendarRule::factory()->create([
                'organization_id' => $organization->id,
                'level' => CalendarLevel::Organization,
                'shift_id' => $first->id,
                'weekday' => $weekday,
            ]);
        }
        CalendarRule::factory()->create([
            'organization_id' => $organization->id,
            'level' => CalendarLevel::Person,
            'person_id' => $person->id,
            'shift_id' => $second->id,
            'weekday' => 5,
        ]);

        $resolver = app(ShiftResolver::class);
        $friday = now()->timezone(config('app.timezone'))->setDate(2026, 9, 18)->startOfDay();
        $this->assertSame(5, $friday->isoWeekday());
        $this->assertSame('P2', $resolver->forPersonOn($person, $friday)?->code);

        $holiday = now()->timezone(config('app.timezone'))->setDate(2026, 1, 6)->startOfDay();
        $this->assertTrue(\App\Support\CroatianHolidays::isHoliday($holiday));
        $this->assertNull($resolver->forPersonOn($person, $holiday));

        CalendarRule::factory()->create([
            'organization_id' => $organization->id,
            'level' => CalendarLevel::Person,
            'person_id' => $person->id,
            'shift_id' => $first->id,
            'weekday' => $holiday->isoWeekday(),
        ]);
        $this->assertSame('P1', app(ShiftResolver::class)->forPersonOn($person->fresh(), $holiday)?->code);
    }

    public function test_late_punch_is_flagged_but_accepted(): void
    {
        [$owner, $organization, $person] = $this->seedPerson();
        $shift = Shift::factory()->create([
            'organization_id' => $organization->id,
            'starts_at' => '08:00:00',
            'ends_at' => '16:00:00',
            'code' => 'P1',
        ]);
        CalendarRule::factory()->create([
            'organization_id' => $organization->id,
            'level' => CalendarLevel::Organization,
            'shift_id' => $shift->id,
            'weekday' => 5,
        ]);

        $result = app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::In->value,
            'occurred_at' => '2026-09-18 08:12:00',
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Test',
        ]);

        $this->assertSame(ExceptionCode::Late->value, $result['entry']->exception_code);
        $this->assertNotNull($result['punch']->id);
        $this->assertSame($shift->id, $result['entry']->planned_shift_id);

        $onTime = app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::Out->value,
            'occurred_at' => '2026-09-18 16:00:00',
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Test',
        ]);
        $this->assertSame(ExceptionCode::Late->value, $onTime['entry']->exception_code);
    }

    public function test_timesheet_shows_planned_shift_code(): void
    {
        [$owner, $organization, $person] = $this->seedPerson();
        $shift = Shift::factory()->create([
            'organization_id' => $organization->id,
            'code' => 'P1',
            'starts_at' => '08:00:00',
            'ends_at' => '16:00:00',
        ]);
        CalendarRule::factory()->create([
            'organization_id' => $organization->id,
            'level' => CalendarLevel::Organization,
            'shift_id' => $shift->id,
            'weekday' => 5,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.timesheet.index', [
                $organization->slug,
                'from' => '2026-09-18',
                'to' => '2026-09-18',
            ]))
            ->assertOk()
            ->assertSee('P1');

        $this->actingAs($owner)
            ->get(route('organization.timesheet.day', [$organization->slug, $person, '2026-09-18']))
            ->assertOk()
            ->assertSee('Plan:')
            ->assertSee('P1');
    }

    public function test_employee_cannot_manage_schedule_and_manager_can_view(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);
        $manager = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $manager->id,
            'role' => OrganizationRole::Manager,
        ]);

        $this->actingAs($employee)
            ->get(route('organization.schedule.index', $organization->slug))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('organization.schedule.index', $organization->slug))
            ->assertOk()
            ->assertDontSee('Nova smjena');

        $this->actingAs($manager)
            ->post(route('organization.schedule.shifts.store', $organization->slug), [
                'name' => 'Zabranjeno',
                'starts_at' => '08:00',
                'ends_at' => '16:00',
            ])
            ->assertForbidden();
    }

    public function test_department_rule_applies_before_organization(): void
    {
        [$owner, $organization, $person] = $this->seedPerson();
        $department = Department::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Operativa',
            'code' => 'OPS',
        ]);
        $person->update(['department_id' => $department->id]);
        $orgShift = Shift::factory()->create([
            'organization_id' => $organization->id,
            'code' => 'ORG',
            'starts_at' => '08:00:00',
            'ends_at' => '16:00:00',
        ]);
        $deptShift = Shift::factory()->create([
            'organization_id' => $organization->id,
            'code' => 'OPS',
            'starts_at' => '07:00:00',
            'ends_at' => '15:00:00',
        ]);
        CalendarRule::factory()->create([
            'organization_id' => $organization->id,
            'level' => CalendarLevel::Organization,
            'shift_id' => $orgShift->id,
            'weekday' => 5,
        ]);
        CalendarRule::factory()->create([
            'organization_id' => $organization->id,
            'level' => CalendarLevel::Department,
            'department_id' => $department->id,
            'shift_id' => $deptShift->id,
            'weekday' => 5,
        ]);

        $friday = now()->timezone(config('app.timezone'))->setDate(2026, 9, 18)->startOfDay();
        $this->assertSame('OPS', app(ShiftResolver::class)->forPersonOn($person->fresh(), $friday)?->code);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Raspored d.o.o.',
            'slug' => 'raspored-firma-'.uniqid(),
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
     * @return array{0: User, 1: Organization, 2: Person}
     */
    private function seedPerson(): array
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Smjena',
            'status' => PersonStatus::Employee,
        ]);

        return [$owner, $organization, $person];
    }
}
