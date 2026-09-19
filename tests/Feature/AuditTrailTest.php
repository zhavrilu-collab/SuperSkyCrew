<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\ClockChannel;
use App\Enums\DocumentKind;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Models\AuditEvent;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use App\Services\ClockService;
use App\Services\PeriodLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_punch_is_audited_web_punch_is_not(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner, 'trag-punch');
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Trag',
            'status' => PersonStatus::Employee,
        ]);

        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::In->value,
            'occurred_at' => now()->startOfDay()->addHours(8)->toDateTimeString(),
            'channel' => ClockChannel::Web->value,
        ]);
        $this->assertSame(0, AuditEvent::query()->count());

        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::Out->value,
            'occurred_at' => now()->startOfDay()->addHours(16)->toDateTimeString(),
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Zaboravljena odjava',
        ]);

        $this->assertSame(1, AuditEvent::query()->count());
        $event = AuditEvent::query()->first();
        $this->assertSame(AuditAction::PunchManual, $event->action);
        $this->assertSame($person->id, $event->person_id);
        $this->assertSame($owner->id, $event->actor_user_id);
        $this->assertStringContainsString('Zaboravljena odjava', $event->summary);
    }

    public function test_lock_handover_and_inspection_appear_on_settings_log(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner, 'trag-izvoz');
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Lana',
            'last_name' => 'Predaja',
            'status' => PersonStatus::Employee,
        ]);

        app(PeriodLockService::class)->lock($organization, 2026, 8, $owner);

        $this->actingAs($owner)
            ->post(route('organization.handovers.store', $organization->slug), [
                'handed_on' => '2026-09-18',
                'recipient' => 'Inspektorat rada',
                'purpose' => 'Nadzor',
                'document_kind' => DocumentKind::TimeRecord->value,
                'person_id' => $person->id,
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('organization.timesheet.inspection', [
                'slug' => $organization->slug,
                'from' => '2026-09-01',
                'to' => '2026-09-19',
            ]))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'podaci',
                'section' => 'trag',
            ]))
            ->assertOk()
            ->assertSee('Revizijski trag')
            ->assertSee('Zaključavanje razdoblja')
            ->assertSee('08/2026')
            ->assertSee('Predaja dokumenta')
            ->assertSee('Inspektorat rada')
            ->assertSee('Inspekcijski izvoz')
            ->assertSee('kartica-kontejner', false);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'podaci',
                'section' => 'trag',
                'radnja' => AuditAction::PeriodLock->value,
            ]))
            ->assertOk()
            ->assertSee('08/2026')
            ->assertDontSee('Inspektorat rada');
    }

    public function test_hire_is_audited_and_employee_cannot_open_log(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner, 'trag-hire');
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);
        $candidate = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Marta',
            'last_name' => 'Kandidat',
            'status' => PersonStatus::Candidate,
            'started_at' => null,
        ]);

        $this->actingAs($owner)
            ->post(route('organization.people.hire', [$organization->slug, $candidate]))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_events', [
            'organization_id' => $organization->id,
            'person_id' => $candidate->id,
            'action' => AuditAction::PersonHire->value,
        ]);

        $this->actingAs($employee)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'podaci',
                'section' => 'trag',
            ]))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role, string $slug): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Trag d.o.o.',
            'slug' => $slug,
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
