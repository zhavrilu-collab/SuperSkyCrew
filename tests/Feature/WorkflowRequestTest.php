<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\Punch;
use App\Models\TimeEntry;
use App\Models\User;
use App\Notifications\WorkflowRequestNotification;
use App\Services\HrSetupService;
use App\Services\LeaveService;
use App\Services\WorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkflowRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_submit_annual_leave_and_owner_approves_into_timesheet(): void
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

        $this->assertDatabaseHas('workflow_requests', [
            'person_id' => $person->id,
            'type' => RequestType::LeaveAnnual->value,
            'status' => RequestStatus::Pending->value,
            'current_role' => OrganizationRole::Manager->value,
        ]);

        Notification::assertSentTo($owner, function (WorkflowRequestNotification $notification) use ($owner, $organization) {
            $mail = $notification->toMail($owner);

            return $notification->event === 'waiting'
                && str_contains($mail->actionUrl, '/'.$organization->slug.'/odobrenja')
                && ! str_contains($mail->actionUrl, '/odobrenja/'.$notification->request->id);
        });

        $zahtjev = \App\Models\WorkflowRequest::query()->first();

        $this->actingAs($owner)
            ->post(route('organization.approvals.approve', [$organization->slug, $zahtjev]))
            ->assertRedirect(route('organization.approvals.index', $organization->slug));

        $this->assertDatabaseHas('time_entries', [
            'person_id' => $person->id,
            'absence_code' => 'GO',
            'status' => 'complete',
        ]);
        $this->assertSame(2, TimeEntry::query()->where('person_id', $person->id)->where('absence_code', 'GO')->count());
        $this->assertSame(18, app(LeaveService::class)->snapshot($person->fresh(), 2026)['remaining']);
        $this->assertDatabaseHas('person_documents', [
            'person_id' => $person->id,
            'title' => 'GO-'.$zahtjev->id.'/2026',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.timesheet.index', [
                'slug' => $organization->slug,
                'from' => '2026-09-21',
                'to' => '2026-09-22',
            ]))
            ->assertOk()
            ->assertSee('GO');

        $this->actingAs($owner)
            ->get(route('organization.requests.decision', [$organization->slug, $zahtjev]))
            ->assertOk()
            ->assertSee('RJEŠENJE')
            ->assertSee($person->fullName())
            ->assertSee('GO-'.$zahtjev->id.'/2026')
            ->assertSee('18');

        $this->actingAs($worker)
            ->get(route('organization.requests.decision', [$organization->slug, $zahtjev]))
            ->assertOk()
            ->assertSee('Rješenje o godišnjem odmoru');
    }

    public function test_leave_decision_is_hidden_until_approved(): void
    {
        Notification::fake();
        [$owner, $worker, $organization] = $this->seedOrgWithWorker();

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::LeaveAnnual->value,
                'from' => '2026-09-21',
                'to' => '2026-09-21',
            ]);

        $zahtjev = \App\Models\WorkflowRequest::query()->first();

        $this->actingAs($owner)
            ->get(route('organization.requests.decision', [$organization->slug, $zahtjev]))
            ->assertNotFound();
    }

    public function test_employee_cannot_open_approvals_inbox(): void
    {
        [, $worker, $organization] = $this->seedOrgWithWorker();

        $this->actingAs($worker)
            ->get(route('organization.approvals.index', $organization->slug))
            ->assertForbidden();
    }

    public function test_reject_requires_reason(): void
    {
        Notification::fake();
        [$owner, $worker, $organization] = $this->seedOrgWithWorker();

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::LeaveAnnual->value,
                'from' => '2026-09-21',
                'to' => '2026-09-21',
            ]);

        $zahtjev = \App\Models\WorkflowRequest::query()->first();

        $this->actingAs($owner)
            ->from(route('organization.requests.show', [$organization->slug, $zahtjev]))
            ->post(route('organization.approvals.reject', [$organization->slug, $zahtjev]), [])
            ->assertSessionHasErrors('comment');
    }

    public function test_submitter_can_cancel_pending_request(): void
    {
        Notification::fake();
        [, $worker, $organization] = $this->seedOrgWithWorker();

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::LeaveAnnual->value,
                'from' => '2026-09-21',
                'to' => '2026-09-21',
            ]);

        $zahtjev = \App\Models\WorkflowRequest::query()->first();

        $this->actingAs($worker)
            ->post(route('organization.requests.cancel', [$organization->slug, $zahtjev]))
            ->assertRedirect();

        $this->assertSame(RequestStatus::Cancelled, $zahtjev->fresh()->status);
    }

    public function test_insufficient_leave_balance_is_rejected(): void
    {
        Notification::fake();
        [, $worker, $organization, $person] = $this->seedOrgWithWorker();
        $person->update(['annual_leave_days' => 1]);

        $this->actingAs($worker)
            ->from(route('organization.requests.create', $organization->slug))
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::LeaveAnnual->value,
                'from' => '2026-09-21',
                'to' => '2026-09-25',
            ])
            ->assertSessionHasErrors('to');
    }

    public function test_overlapping_request_is_rejected(): void
    {
        Notification::fake();
        [, $worker, $organization] = $this->seedOrgWithWorker();

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::LeaveAnnual->value,
                'from' => '2026-09-21',
                'to' => '2026-09-22',
            ])
            ->assertRedirect();

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::LeaveAnnual->value,
                'from' => '2026-09-22',
                'to' => '2026-09-23',
            ])
            ->assertSessionHasErrors('from');
    }

    public function test_old_leave_is_consumed_before_new(): void
    {
        [, , , $person] = $this->seedOrgWithWorker();
        $leave = app(LeaveService::class);
        $balance = $leave->ensureBalance($person, 2026);
        $balance->update(['carried_days' => 5, 'entitled_days' => 20, 'used_days' => 0]);

        $leave->consume($person, 3, 2026);
        $snapshot = $leave->snapshot($person->fresh(), 2026);

        $this->assertSame(2, $snapshot['remaining_old']);
        $this->assertSame(20, $snapshot['remaining_new']);
        $this->assertSame(22, $snapshot['remaining']);
    }

    public function test_holiday_is_not_counted_as_leave_day(): void
    {
        Notification::fake();
        [, $worker, $organization] = $this->seedOrgWithWorker();

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::LeaveAnnual->value,
                'from' => '2026-04-30',
                'to' => '2026-05-01',
            ])
            ->assertRedirect();

        $zahtjev = \App\Models\WorkflowRequest::query()->first();
        $this->assertSame(1, $zahtjev->days());
        $this->assertSame(['2026-04-30'], $zahtjev->payload['dates']);
    }

    public function test_longer_leave_goes_to_hr_not_manager(): void
    {
        Notification::fake();
        [$owner, $worker, $organization] = $this->seedOrgWithWorker();
        $hr = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $hr->id,
            'role' => OrganizationRole::Hr,
        ]);

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::LeaveAnnual->value,
                'from' => '2026-09-21',
                'to' => '2026-09-25',
            ]);

        $zahtjev = \App\Models\WorkflowRequest::query()->first();
        $this->assertSame(OrganizationRole::Hr->value, $zahtjev->current_role);
        $this->assertTrue(app(WorkflowEngine::class)->canAct($zahtjev, $hr));
        $this->assertTrue(app(WorkflowEngine::class)->canAct($zahtjev, $owner));
    }

    public function test_overtime_request_is_written_after_approval(): void
    {
        Notification::fake();
        [$owner, $worker, $organization, $person] = $this->seedOrgWithWorker();

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::Overtime->value,
                'from' => '2026-09-18',
                'minutes' => 90,
                'note' => 'Završetak isporuke',
            ])
            ->assertRedirect();

        $zahtjev = \App\Models\WorkflowRequest::query()->first();
        $this->assertSame(RequestType::Overtime, $zahtjev->type);
        $this->assertSame(90, $zahtjev->minutes());
        $this->assertSame(OrganizationRole::Manager->value, $zahtjev->current_role);

        $this->actingAs($owner)
            ->post(route('organization.approvals.approve', [$organization->slug, $zahtjev]))
            ->assertRedirect();

        $entry = TimeEntry::query()->where('person_id', $person->id)->first();
        $this->assertSame(90, $entry->overtime_minutes);
        $this->assertSame(90, $entry->approved_overtime_minutes);
        $this->assertSame(90, $entry->evidential_minutes);
    }

    public function test_punch_correction_creates_new_punch_and_rebuilds_day(): void
    {
        Notification::fake();
        [$owner, $worker, $organization, $person] = $this->seedOrgWithWorker();

        $clock = app(\App\Services\ClockService::class);
        $clock->punch($person, $worker, [
            'type' => PunchType::In->value,
            'occurred_at' => '2026-09-18 08:00:00',
        ]);
        $clock->punch($person, $worker, [
            'type' => PunchType::Out->value,
            'occurred_at' => '2026-09-18 16:00:00',
        ]);

        $out = Punch::query()->where('person_id', $person->id)->where('type', PunchType::Out)->first();

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::PunchCorrection->value,
                'punch_id' => $out->id,
                'occurred_at' => '2026-09-18 17:00',
                'note' => 'Zaboravljena odjava sat vremena kasnije',
            ])
            ->assertRedirect();

        $zahtjev = \App\Models\WorkflowRequest::query()->where('type', RequestType::PunchCorrection)->first();

        $this->actingAs($owner)
            ->post(route('organization.approvals.approve', [$organization->slug, $zahtjev]))
            ->assertRedirect();

        $this->assertSame(3, Punch::query()->where('person_id', $person->id)->count());
        $this->assertDatabaseHas('punches', [
            'person_id' => $person->id,
            'correction_of_id' => $out->id,
            'channel' => ClockChannel::Workflow->value,
        ]);

        $entry = TimeEntry::query()->where('person_id', $person->id)->first();
        $this->assertSame(540, $entry->total_minutes);
        $this->assertSame(60, $entry->overtime_minutes);
        $this->assertTrue($out->fresh()->corrections()->exists());
    }

    public function test_overtime_without_reason_is_rejected(): void
    {
        Notification::fake();
        [, $worker, $organization] = $this->seedOrgWithWorker();

        $this->actingAs($worker)
            ->from(route('organization.requests.create', $organization->slug))
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::Overtime->value,
                'from' => '2026-09-18',
                'minutes' => 60,
            ])
            ->assertSessionHasErrors('note');
    }

    public function test_personal_data_change_is_applied_after_hr_approval(): void
    {
        Notification::fake();
        [$owner, $worker, $organization, $person] = $this->seedOrgWithWorker();
        $person->update(['residence' => 'Zagreb, Ilica 1']);

        $this->actingAs($worker)
            ->get(route('organization.requests.create', [$organization->slug, 'type' => 'personal_data']))
            ->assertOk()
            ->assertSee('Promjena podataka')
            ->assertSee('Datum nastanka');

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::PersonalDataChange->value,
                'occurred_on' => now()->subDays(2)->toDateString(),
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'residence' => 'Split, Riva 12',
                'citizenship' => $person->citizenship,
                'note' => 'Selidba',
            ])
            ->assertRedirect();

        $zahtjev = \App\Models\WorkflowRequest::query()->where('type', RequestType::PersonalDataChange)->first();
        $this->assertNotNull($zahtjev);
        $this->assertSame(OrganizationRole::Hr->value, $zahtjev->current_role);
        $this->assertFalse($zahtjev->isLatePersonalData());
        $this->assertSame(1, $zahtjev->changeCount());
        $this->assertFalse(app(WorkflowEngine::class)->canAct($zahtjev, $worker));

        $this->actingAs($owner)
            ->get(route('organization.requests.show', [$organization->slug, $zahtjev]))
            ->assertOk()
            ->assertSee('Split, Riva 12')
            ->assertSee('Zagreb, Ilica 1');

        $this->actingAs($owner)
            ->post(route('organization.approvals.approve', [$organization->slug, $zahtjev]))
            ->assertRedirect();

        $this->assertSame('Split, Riva 12', $person->fresh()->residence);
        $this->assertSame(0, TimeEntry::query()->where('person_id', $person->id)->count());
    }

    public function test_personal_data_change_without_diff_is_rejected_and_late_is_flagged(): void
    {
        Notification::fake();
        [, $worker, $organization, $person] = $this->seedOrgWithWorker();

        $this->actingAs($worker)
            ->from(route('organization.requests.create', $organization->slug))
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::PersonalDataChange->value,
                'occurred_on' => now()->toDateString(),
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'citizenship' => $person->citizenship,
                'residence' => $person->residence,
            ])
            ->assertSessionHasErrors('residence');

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::PersonalDataChange->value,
                'occurred_on' => now()->subDays(12)->toDateString(),
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'residence' => 'Rijeka, Korzo 3',
            ])
            ->assertRedirect();

        $zahtjev = \App\Models\WorkflowRequest::query()->where('type', RequestType::PersonalDataChange)->first();
        $this->assertTrue($zahtjev->isLatePersonalData());
        $this->assertSame(12, $zahtjev->payload['days_since']);
    }

    /**
     * @return array{0: User, 1: User, 2: Organization, 3: Person}
     */
    private function seedOrgWithWorker(): array
    {
        $owner = User::factory()->create();
        $worker = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'GO d.o.o.',
            'slug' => 'go-firma',
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
            'status' => PersonStatus::Employee,
            'manager_user_id' => $owner->id,
            'annual_leave_days' => 20,
        ]);

        app(HrSetupService::class)->provision($organization);

        return [$owner, $worker, $organization, $person];
    }
}
