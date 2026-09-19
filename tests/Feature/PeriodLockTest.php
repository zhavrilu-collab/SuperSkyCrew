<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Enums\RequestType;
use App\Enums\TimeEntryStatus;
use App\Models\ComplianceExport;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\PeriodLock;
use App\Models\Person;
use App\Models\Punch;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ClockService;
use App\Services\HrSetupService;
use App\Services\PeriodLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PeriodLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_lock_month_and_export_inspection(): void
    {
        [$owner, $organization, $person] = $this->seedOrg(OrganizationRole::Owner);

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
            ->get(route('organization.timesheet.index', $organization->slug))
            ->assertOk()
            ->assertSee('Zaključaj razdoblje')
            ->assertSee('Inspekcija')
            ->assertSee('Izvoz CSV');

        $this->actingAs($owner)
            ->from(route('organization.timesheet.index', $organization->slug))
            ->post(route('organization.timesheet.lock', $organization->slug), [
                'year' => now()->year,
                'month' => now()->month,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('period_locks', [
            'organization_id' => $organization->id,
            'year' => now()->year,
            'month' => now()->month,
        ]);
        $this->assertSame(
            TimeEntryStatus::Locked,
            TimeEntry::query()->where('person_id', $person->id)->first()->status,
        );

        $this->actingAs($owner)
            ->get(route('organization.timesheet.index', $organization->slug))
            ->assertOk()
            ->assertSee('je zaključano')
            ->assertDontSee('Zaključaj razdoblje');

        $this->actingAs($owner)
            ->get(route('organization.timesheet.day', [
                $organization->slug,
                $person,
                now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('zaključan')
            ->assertDontSee('Spremi punch');

        $this->actingAs($owner)
            ->get(route('organization.timesheet.inspection', [
                'slug' => $organization->slug,
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('NN 55/2024')
            ->assertSee($person->fullName())
            ->assertSee('čl. 18.')
            ->assertSee('Zbirno po osobi')
            ->assertSee('Excel (CSV)');

        $this->assertDatabaseHas('compliance_exports', [
            'organization_id' => $organization->id,
            'kind' => 'inspection',
            'user_id' => $owner->id,
        ]);

        $csv = $this->actingAs($owner)
            ->get(route('organization.timesheet.export', [
                'slug' => $organization->slug,
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]));

        $csv->assertOk();
        $csv->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Evidencijski min', $csv->streamedContent());
        $this->assertStringContainsString($person->fullName(), $csv->streamedContent());
        $this->assertDatabaseHas('compliance_exports', [
            'organization_id' => $organization->id,
            'kind' => 'payroll',
        ]);
    }

    public function test_locked_period_rejects_new_punch(): void
    {
        [$owner, $organization, $person] = $this->seedOrg(OrganizationRole::Owner);

        app(PeriodLockService::class)->lock($organization, (int) now()->year, (int) now()->month, $owner);

        try {
            app(ClockService::class)->punch($person, $owner, [
                'type' => PunchType::In->value,
                'occurred_at' => now()->toDateTimeString(),
                'channel' => ClockChannel::Manager->value,
                'reason' => 'Kasno',
            ]);
            $this->fail('Očekivana je ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('occurred_at', $e->errors());
        }

        $this->assertSame(0, Punch::query()->where('person_id', $person->id)->count());
        $this->assertSame(1, PeriodLock::query()->count());
    }

    public function test_locked_period_rejects_annual_leave(): void
    {
        Notification::fake();
        [$owner, $organization, $person] = $this->seedOrg(OrganizationRole::Owner);
        $worker = User::query()->find($person->user_id);
        app(HrSetupService::class)->provision($organization);
        app(PeriodLockService::class)->lock($organization, 2026, 9, $owner);

        $this->actingAs($worker)
            ->from(route('organization.requests.create', $organization->slug))
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::LeaveAnnual->value,
                'from' => '2026-09-21',
                'to' => '2026-09-22',
            ])
            ->assertSessionHasErrors('to');
    }

    public function test_manager_cannot_lock_or_open_inspection(): void
    {
        [$manager, $organization] = $this->seedOrg(OrganizationRole::Manager);

        $this->actingAs($manager)
            ->get(route('organization.timesheet.index', $organization->slug))
            ->assertOk()
            ->assertDontSee('Zaključaj razdoblje')
            ->assertDontSee('Inspekcija');

        $this->actingAs($manager)
            ->post(route('organization.timesheet.lock', $organization->slug), [
                'year' => now()->year,
                'month' => now()->month,
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('organization.timesheet.inspection', $organization->slug))
            ->assertForbidden();
    }

    public function test_accountant_can_export_csv_but_not_lock(): void
    {
        [$accountant, $organization, $person] = $this->seedOrg(OrganizationRole::Accountant);

        $this->actingAs($accountant)
            ->get(route('organization.timesheet.index', $organization->slug))
            ->assertOk()
            ->assertSee('Izvoz CSV')
            ->assertDontSee('Zaključaj razdoblje')
            ->assertDontSee('Inspekcija');

        $this->actingAs($accountant)
            ->get(route('organization.timesheet.export', [
                'slug' => $organization->slug,
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk();

        $this->assertTrue(
            ComplianceExport::query()
                ->where('organization_id', $organization->id)
                ->where('kind', 'payroll')
                ->exists()
        );

        $this->actingAs($accountant)
            ->post(route('organization.timesheet.lock', $organization->slug), [
                'year' => now()->year,
                'month' => now()->month,
            ])
            ->assertForbidden();

        $this->actingAs($accountant)
            ->get(route('organization.timesheet.inspection', $organization->slug))
            ->assertForbidden();

        $this->assertDatabaseMissing('period_locks', ['organization_id' => $organization->id]);
        $this->assertSame($person->organization_id, $organization->id);
    }

    public function test_previous_month_locks_automatically_from_configured_day(): void
    {
        [$owner, $organization] = $this->seedOrg(OrganizationRole::Owner);
        $organization->update(['period_lock_day' => 8]);
        $this->travelTo('2026-09-08 09:00:00');

        $lock = app(PeriodLockService::class)->lockPreviousIfDue($organization);

        $this->assertNotNull($lock);
        $this->assertSame(2026, $lock->year);
        $this->assertSame(8, $lock->month);
        $this->assertNull($lock->locked_by_user_id);
        $this->assertNull(app(PeriodLockService::class)->lockPreviousIfDue($organization));
    }

    public function test_automatic_lock_waits_until_configured_day_and_can_be_disabled(): void
    {
        [$owner, $organization] = $this->seedOrg(OrganizationRole::Owner);
        $organization->update(['period_lock_day' => 8]);
        $this->travelTo('2026-09-07 09:00:00');
        $this->assertNull(app(PeriodLockService::class)->lockPreviousIfDue($organization));

        $organization->update(['period_lock_day' => 0]);
        $this->travelTo('2026-09-19 09:00:00');
        $this->assertNull(app(PeriodLockService::class)->lockPreviousIfDue($organization));
    }

    public function test_close_yesterday_marks_missing_out(): void
    {
        [$owner, $organization, $person] = $this->seedOrg(OrganizationRole::Owner);
        $this->travelTo('2026-09-18 16:00:00');
        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::In->value,
            'occurred_at' => '2026-09-18 08:00:00',
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Prijava',
        ]);

        $this->travelTo('2026-09-19 08:00:00');
        $this->assertSame(1, app(\App\Services\TimeCloseService::class)->closeYesterday($organization));

        $entry = TimeEntry::query()->where('person_id', $person->id)->first();
        $this->assertSame(\App\Enums\ExceptionCode::MissingOut->value, $entry->exception_code);
        $this->assertSame(TimeEntryStatus::Complete, $entry->status);
    }

    public function test_owner_can_save_lock_calendar_and_command_locks_previous_month(): void
    {
        [$owner, $organization] = $this->seedOrg(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->put(route('organization.settings.period-lock', $organization->slug), [
                'period_lock_day' => 8,
                'show_clock_bounds' => 1,
            ])
            ->assertRedirect();
        $this->assertSame(8, $organization->fresh()->period_lock_day);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'vrijeme',
                'section' => 'zakljucavanje',
            ]))
            ->assertOk()
            ->assertSee('Zaključavanje')
            ->assertSee('hr:close-time')
            ->assertSee('8. u mjesecu')
            ->assertSee('početak i završetak');

        $this->travelTo('2026-09-08 09:00:00');
        $this->artisan('hr:close-time')
            ->expectsOutput('Zatvoreno dana: 0; zaključano razdoblja: 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('period_locks', [
            'organization_id' => $organization->id,
            'year' => 2026,
            'month' => 8,
            'locked_by_user_id' => null,
        ]);
    }

    /**
     * @return array{0: User, 1: Organization, 2: Person}
     */
    private function seedOrg(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Zaključaj d.o.o.',
            'slug' => 'zakljucaj-firma',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'oib' => '12345678903',
        ]);

        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        $personUser = $user;
        if ($role !== OrganizationRole::Employee) {
            $personUser = User::factory()->create();
            OrganizationUser::query()->create([
                'organization_id' => $organization->id,
                'user_id' => $personUser->id,
                'role' => OrganizationRole::Employee,
            ]);
        }

        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $personUser->id,
            'first_name' => 'Marta',
            'last_name' => 'Horvat',
            'status' => PersonStatus::Employee,
            'annual_leave_days' => 20,
        ]);

        return [$user, $organization, $person];
    }
}
