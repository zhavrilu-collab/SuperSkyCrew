<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\ExceptionCode;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Enums\TimeEntryStatus;
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

class ClockPwaOfflineTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_page_shows_week_hours_and_pwa_assets(): void
    {
        [$user, $organization, $person] = $this->seedClockableEmployee();
        TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'work_date' => now()->toDateString(),
            'total_minutes' => 90,
            'evidential_minutes' => 90,
            'status' => TimeEntryStatus::Complete,
            'exception_code' => ExceptionCode::Late->value,
        ]);

        $this->actingAs($user)
            ->get(route('organization.clock', $organization->slug))
            ->assertOk()
            ->assertSee('Tjedni fond')
            ->assertSee('1.5 h')
            ->assertSee('Otvorene iznimke')
            ->assertSee('Kašnjenje vs plan')
            ->assertSee('Čeka slanje')
            ->assertSee('clock-pwa.js')
            ->assertSee('clock-sw.js');

        $this->actingAs($user)
            ->get(route('organization.clock.manifest', $organization->slug))
            ->assertOk()
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('short_name', 'Prijava');
    }

    public function test_delayed_offline_punch_keeps_device_time_and_records_ip(): void
    {
        [$user, $organization, $person] = $this->seedClockableEmployee();
        $deviceAt = now()->subHours(3);

        $this->actingAs($user)
            ->postJson(route('organization.clock.store', $organization->slug), [
                'type' => PunchType::In->value,
                'from_pwa' => true,
                'offline' => true,
                'occurred_at' => $deviceAt->toIso8601String(),
                'client_event_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                'device_id' => 'pwa-teren',
            ])
            ->assertOk()
            ->assertJsonPath('punch.offline', true);

        $punch = Punch::query()->where('person_id', $person->id)->first();
        $this->assertNotNull($punch);
        $this->assertTrue($punch->offline);
        $this->assertSame(ClockChannel::Pwa, $punch->channel);
        $this->assertSame($deviceAt->timezone(config('app.timezone'))->format('Y-m-d H:i'), $punch->occurred_at_device->format('Y-m-d H:i'));
        $this->assertTrue($punch->occurred_at_server->greaterThan($punch->occurred_at_device));
        $this->assertNotEmpty($punch->client_ip);
    }

    public function test_offline_punch_older_than_seven_days_is_rejected(): void
    {
        [$user, , $person] = $this->seedClockableEmployee();

        try {
            app(ClockService::class)->punch($person, $user, [
                'type' => PunchType::In->value,
                'channel' => ClockChannel::Pwa->value,
                'offline' => true,
                'occurred_at' => now()->subDays(8)->toDateTimeString(),
            ]);
            $this->fail('Očekivana je ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('occurred_at', $e->errors());
        }

        $this->assertSame(0, Punch::query()->where('person_id', $person->id)->count());
    }

    public function test_future_device_time_is_rejected_on_pwa(): void
    {
        [$user, , $person] = $this->seedClockableEmployee();

        $this->expectException(ValidationException::class);

        app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Pwa->value,
            'occurred_at' => now()->addHour()->toDateTimeString(),
        ]);
    }

    /**
     * @return array{0: User, 1: Organization, 2: Person}
     */
    private function seedClockableEmployee(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Teren d.o.o.',
            'slug' => 'teren-'.uniqid(),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Employee,
        ]);
        $location = Location::factory()->create([
            'organization_id' => $organization->id,
            'allow_offline' => true,
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => PersonStatus::Employee,
            'location_id' => $location->id,
        ]);

        return [$user, $organization, $person];
    }
}
