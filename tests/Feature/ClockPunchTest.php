<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\GeofenceMode;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\Punch;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ClockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClockPunchTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_without_person_card_is_redirected_from_clock(): void
    {
        [$user, $organization] = $this->seedMember(OrganizationRole::Employee);

        $this->actingAs($user)
            ->get(route('organization.clock', $organization->slug))
            ->assertRedirect(route('organization.dashboard', $organization->slug));
    }

    public function test_employee_can_clock_in_and_out_via_web(): void
    {
        [$user, $organization, $person] = $this->seedClockableEmployee();

        $this->actingAs($user)
            ->get(route('organization.clock', $organization->slug))
            ->assertOk()
            ->assertSee('Niste prijavljeni')
            ->assertSee($person->fullName());

        $this->actingAs($user)
            ->post(route('organization.clock.store', $organization->slug), [
                'type' => PunchType::In->value,
                'client_event_id' => '11111111-1111-4111-8111-111111111111',
            ])
            ->assertRedirect(route('organization.clock', $organization->slug));

        $this->assertDatabaseHas('punches', [
            'person_id' => $person->id,
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Web->value,
        ]);

        $this->actingAs($user)
            ->get(route('organization.clock', $organization->slug))
            ->assertOk()
            ->assertSee('Prijavljeni');

        $this->actingAs($user)
            ->post(route('organization.clock.store', $organization->slug), [
                'type' => PunchType::Out->value,
                'client_event_id' => '22222222-2222-4222-8222-222222222222',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'person_id' => $person->id,
            'status' => 'complete',
        ]);
    }

    public function test_json_clock_returns_next_state(): void
    {
        [$user, $organization, $person] = $this->seedClockableEmployee();

        $this->actingAs($user)
            ->postJson(route('organization.clock.store', $organization->slug), [
                'type' => PunchType::In->value,
                'from_pwa' => true,
                'client_event_id' => '33333333-3333-4333-8333-333333333333',
            ])
            ->assertOk()
            ->assertJsonPath('clocked_in', true)
            ->assertJsonPath('next_type', PunchType::Out->value)
            ->assertJsonPath('punch.type', PunchType::In->value);

        $this->assertDatabaseHas('punches', [
            'person_id' => $person->id,
            'channel' => ClockChannel::Pwa->value,
        ]);
    }

    public function test_clock_api_route_accepts_json_punch(): void
    {
        [$user, $organization, $person] = $this->seedClockableEmployee();

        $this->actingAs($user)
            ->postJson(route('organization.clock.api', $organization->slug), [
                'type' => PunchType::In->value,
                'client_event_id' => '55555555-5555-4555-8555-555555555555',
            ])
            ->assertOk()
            ->assertJsonPath('clocked_in', true)
            ->assertJsonPath('next_type', PunchType::Out->value)
            ->assertJsonPath('state', PunchType::In->value)
            ->assertJsonPath('entry_status', 'draft');

        $this->assertDatabaseHas('punches', [
            'person_id' => $person->id,
            'type' => PunchType::In->value,
        ]);
    }

    public function test_duplicate_client_event_id_does_not_create_second_punch(): void
    {
        [$user, $organization, $person] = $this->seedClockableEmployee();
        $eventId = '44444444-4444-4444-8444-444444444444';

        $this->actingAs($user)
            ->post(route('organization.clock.store', $organization->slug), [
                'type' => PunchType::In->value,
                'client_event_id' => $eventId,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('organization.clock.store', $organization->slug), [
                'type' => PunchType::In->value,
                'client_event_id' => $eventId,
            ])
            ->assertRedirect();

        $this->assertSame(1, Punch::query()->where('person_id', $person->id)->count());
    }

    public function test_employee_cannot_punch_another_person(): void
    {
        [$user, $organization, $person] = $this->seedClockableEmployee();
        $other = Person::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->post(route('organization.clock.store', $organization->slug), [
                'type' => PunchType::In->value,
                'person_id' => $other->id,
                'client_event_id' => '55555555-5555-4555-8555-555555555555',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('punches', ['person_id' => $person->id]);
        $this->assertDatabaseMissing('punches', ['person_id' => $other->id]);
    }

    public function test_strict_geofence_rejects_out_of_zone_punch(): void
    {
        [$user, $organization, $person] = $this->seedClockableEmployee();
        $location = Location::factory()->create([
            'organization_id' => $organization->id,
            'latitude' => 45.8150,
            'longitude' => 15.9819,
            'radius_meters' => 100,
            'geofence_mode' => GeofenceMode::Strict,
        ]);
        $person->update(['location_id' => $location->id]);
        $person->refresh();

        $this->expectException(ValidationException::class);

        app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::In->value,
            'latitude' => 45.0,
            'longitude' => 15.0,
        ]);
    }

    public function test_manager_channel_requires_reason(): void
    {
        [$user, $organization, $person] = $this->seedClockableEmployee();

        $this->expectException(ValidationException::class);

        app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Manager->value,
        ]);
    }

    public function test_candidate_cannot_clock_in(): void
    {
        [$user, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => PersonStatus::Candidate,
        ]);

        $this->expectException(ValidationException::class);

        app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::In->value,
        ]);
    }

    public function test_locked_time_entry_rejects_punch(): void
    {
        [$user, $organization, $person] = $this->seedClockableEmployee();

        TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'work_date' => now()->toDateString(),
            'total_minutes' => 100,
            'evidential_minutes' => 100,
            'status' => 'locked',
        ]);

        try {
            app(ClockService::class)->punch($person, $user, [
                'type' => PunchType::In->value,
                'occurred_at' => now()->toDateTimeString(),
            ]);
            $this->fail('Očekivana je ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('occurred_at', $e->errors());
        }

        $this->assertSame(0, Punch::query()->where('person_id', $person->id)->count());
        $this->assertSame(100, TimeEntry::query()->where('person_id', $person->id)->value('total_minutes'));
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Sat d.o.o.',
            'slug' => 'sat-firma',
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
    private function seedClockableEmployee(): array
    {
        [$user, $organization] = $this->seedMember(OrganizationRole::Employee);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => PersonStatus::Employee,
        ]);

        return [$user, $organization, $person];
    }
}
