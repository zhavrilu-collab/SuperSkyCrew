<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\ContractType;
use App\Enums\ExceptionCode;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\GrantEntry;
use App\Models\GrantProject;
use App\Models\Location;
use App\Models\OpenShift;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\Punch;
use App\Models\Shift;
use App\Models\ShiftOverride;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ClockService;
use App\Services\HrSetupService;
use App\Support\CroatianHolidays;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Faza1Faza2Test extends TestCase
{
    use RefreshDatabase;

    public function test_employee_limit_blocks_new_counted_person(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $organization->update(['employee_limit' => 1]);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Ana',
                'last_name' => 'Nova',
                'status' => PersonStatus::Employee->value,
                'contract_type' => ContractType::Indefinite->value,
                'job_title' => 'Referent',
                'started_at' => '2026-01-15',
                'citizenship' => 'HR',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(1, Person::query()->where('organization_id', $organization->id)->count());
    }

    public function test_kiosk_is_hidden_when_feature_is_off(): void
    {
        [$organization, $location] = $this->seedKioskOrg();
        $organization->update(['features' => ['clock_kiosk' => false]]);

        $this->get(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->assertNotFound();
    }

    public function test_location_rejects_disallowed_channel(): void
    {
        [$user, $organization, $person, $location] = $this->seedClockableWithLocation();
        $location->update(['allowed_channels' => [ClockChannel::Kiosk->value]]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Pwa->value,
            'location_id' => $location->id,
            'client_event_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
        ]);
    }

    public function test_clock_token_punches_without_session(): void
    {
        [, $organization, $person] = $this->seedClockableWithLocation();

        $this->withHeaders(['Authorization' => 'Bearer '.$person->clock_api_token])
            ->postJson('/api/'.$organization->slug.'/clock/punches', [
                'type' => PunchType::In->value,
                'client_event_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            ])
            ->assertOk()
            ->assertJsonPath('punch.type', PunchType::In->value);

        $this->assertDatabaseHas('punches', [
            'person_id' => $person->id,
            'channel' => ClockChannel::Api->value,
            'created_by_user_id' => null,
        ]);
    }

    public function test_mock_gps_sets_exception_but_saves_punch(): void
    {
        [$user, , $person] = $this->seedClockableWithLocation();

        $result = app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Pwa->value,
            'occurred_at' => '2026-09-18 08:00:00',
            'mock_gps' => true,
            'gps_accuracy' => 250,
            'client_event_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        ]);
        $this->assertSame('mock_gps', $result['punch']->device_result);

        $result = app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::Out->value,
            'channel' => ClockChannel::Pwa->value,
            'occurred_at' => '2026-09-18 16:00:00',
            'client_event_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccd',
        ]);

        $this->assertSame('mock_gps', Punch::query()->where('person_id', $person->id)->where('type', PunchType::In->value)->value('device_result'));
        $this->assertSame(ExceptionCode::MockGps->value, $result['entry']->exception_code);
    }

    public function test_easter_monday_2026_is_a_holiday(): void
    {
        $this->assertTrue(CroatianHolidays::isHoliday(Carbon::parse('2026-04-05', 'Europe/Zagreb')));
        $this->assertTrue(CroatianHolidays::isHoliday(Carbon::parse('2026-04-06', 'Europe/Zagreb')));
        $this->assertTrue(CroatianHolidays::isHoliday(Carbon::parse('2026-06-04', 'Europe/Zagreb')));
        $this->assertFalse(CroatianHolidays::isWorkingDay(Carbon::parse('2026-04-06', 'Europe/Zagreb')));
    }

    public function test_rounding_snaps_punch_to_location_step(): void
    {
        [$user, , $person, $location] = $this->seedClockableWithLocation();
        $location->update(['punch_round_minutes' => 15]);

        $result = app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::In->value,
            'occurred_at' => '2026-09-18 08:07:00',
            'location_id' => $location->id,
            'client_event_id' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
        ]);

        $this->assertSame('08:00', $result['punch']->occurred_at_device->timezone(config('app.timezone'))->format('H:i'));
    }

    public function test_grant_hours_do_not_touch_time_entry(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);
        $project = GrantProject::query()->create([
            'organization_id' => $organization->id,
            'code' => 'ESF',
            'name' => 'ESF 2026',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('organization.grants.store', $organization->slug), [
                'person_id' => $person->id,
                'grant_project_id' => $project->id,
                'work_date' => '2026-09-18',
                'minutes' => 120,
            ])
            ->assertRedirect();

        $this->assertSame(1, GrantEntry::query()->count());
        $this->assertSame(0, TimeEntry::query()->where('person_id', $person->id)->count());
    }

    public function test_entrance_and_terminal_and_chat_channels(): void
    {
        [$user, $organization, $person, $location] = $this->seedClockableWithLocation();

        $this->actingAs($user)
            ->post(route('organization.entrance.store', [$organization->slug, $location->entrance_token]))
            ->assertRedirect();

        $this->assertDatabaseHas('punches', [
            'person_id' => $person->id,
            'channel' => ClockChannel::Entrance->value,
            'location_id' => $location->id,
        ]);

        app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::Out->value,
            'client_event_id' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
        ]);

        $this->postJson('/api/'.$organization->slug.'/terminal/'.$location->terminal_token.'/punches', [
            'pin' => $person->clock_pin,
            'client_event_id' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
        ])
            ->assertOk()
            ->assertJsonPath('punch.type', PunchType::In->value);

        $this->assertDatabaseHas('punches', [
            'person_id' => $person->id,
            'channel' => ClockChannel::Terminal->value,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$person->clock_api_token])
            ->postJson('/api/'.$organization->slug.'/chat/punches', [
                'text' => 'odjava',
                'client_event_id' => '99999999-9999-4999-8999-999999999999',
            ])
            ->assertOk();

        $this->assertDatabaseHas('punches', [
            'person_id' => $person->id,
            'channel' => ClockChannel::Chat->value,
            'type' => PunchType::Out->value,
        ]);
    }

    public function test_open_shift_claim_writes_override(): void
    {
        [$user, $organization, $person] = $this->seedClockableWithLocation();
        $shift = Shift::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Druga',
            'code' => 'P2',
            'starts_at' => '14:00:00',
            'ends_at' => '22:00:00',
            'break_minutes' => 30,
        ]);
        $open = OpenShift::query()->create([
            'organization_id' => $organization->id,
            'shift_id' => $shift->id,
            'work_date' => '2026-09-22',
            'slots' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('organization.schedule.open.claim', [$organization->slug, $open]))
            ->assertRedirect();

        $this->assertTrue(
            ShiftOverride::query()
                ->where('person_id', $person->id)
                ->where('shift_id', $shift->id)
                ->whereDate('work_date', '2026-09-22')
                ->exists()
        );
    }

    public function test_shift_swap_approval_swaps_overrides(): void
    {
        [$owner, $worker, $organization, $person, $other] = $this->seedSwapPair();
        app(HrSetupService::class)->provision($organization);

        $p1 = Shift::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Prva',
            'code' => 'P1',
            'starts_at' => '08:00:00',
            'ends_at' => '16:00:00',
            'break_minutes' => 30,
        ]);
        $p2 = Shift::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Druga',
            'code' => 'P2',
            'starts_at' => '14:00:00',
            'ends_at' => '22:00:00',
            'break_minutes' => 30,
        ]);
        ShiftOverride::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'shift_id' => $p1->id,
            'work_date' => '2026-09-23',
        ]);
        ShiftOverride::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $other->id,
            'shift_id' => $p2->id,
            'work_date' => '2026-09-23',
        ]);

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::ShiftSwap->value,
                'from' => '2026-09-23',
                'counterpart_id' => $other->id,
                'note' => 'Zamjena',
            ])
            ->assertRedirect();

        $zahtjev = \App\Models\WorkflowRequest::query()->first();
        $this->actingAs($owner)
            ->post(route('organization.approvals.approve', [$organization->slug, $zahtjev]))
            ->assertRedirect();

        $this->assertSame($p2->id, ShiftOverride::query()->where('person_id', $person->id)->value('shift_id'));
        $this->assertSame($p1->id, ShiftOverride::query()->where('person_id', $other->id)->value('shift_id'));
        $this->assertSame(RequestStatus::Approved->value, $zahtjev->fresh()->status->value);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Faza d.o.o.',
            'slug' => 'faza-firma',
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
     * @return array{0: Organization, 1: Location}
     */
    private function seedKioskOrg(): array
    {
        $organization = Organization::query()->create([
            'name' => 'Kiosk d.o.o.',
            'slug' => 'kiosk-off',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        $location = Location::factory()->create([
            'organization_id' => $organization->id,
            'kiosk_enabled' => true,
            'kiosk_token' => 'kiosk-off-token',
        ]);

        return [$organization, $location];
    }

    /**
     * @return array{0: User, 1: Organization, 2: Person, 3: Location}
     */
    private function seedClockableWithLocation(): array
    {
        [$user, $organization] = $this->seedMember(OrganizationRole::Employee);
        $location = Location::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'location_id' => $location->id,
            'status' => PersonStatus::Employee,
            'clock_pin' => '2222',
        ]);

        return [$user, $organization, $person, $location];
    }

    /**
     * @return array{0: User, 1: User, 2: Organization, 3: Person, 4: Person}
     */
    private function seedSwapPair(): array
    {
        $owner = User::factory()->create();
        $worker = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Zamjena d.o.o.',
            'slug' => 'zamjena-firma',
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
            'annual_leave_manual' => true,
        ]);
        $other = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);

        return [$owner, $worker, $organization, $person, $other];
    }
}
