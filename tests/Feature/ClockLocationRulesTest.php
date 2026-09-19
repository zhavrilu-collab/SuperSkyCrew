<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\DeviceBindMode;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\Punch;
use App\Models\User;
use App\Services\ClockService;
use App\Services\RetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClockLocationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_photo_is_rejected_then_stored(): void
    {
        Storage::fake('local');
        [$user, $organization, $person, $location] = $this->seedAtLocation();
        $location->update(['require_photo' => true]);
        $person->refresh();

        try {
            app(ClockService::class)->punch($person, $user, [
                'type' => PunchType::In->value,
                'channel' => ClockChannel::Pwa->value,
            ]);
            $this->fail('Očekivana je ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('photo', $e->errors());
        }

        $file = UploadedFile::fake()->image('selfie.jpg', 40, 40);

        app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Pwa->value,
            'photo' => $file,
        ]);

        $punch = Punch::query()->where('person_id', $person->id)->first();
        $this->assertNotNull($punch?->photo_path);
        Storage::disk('local')->assertExists($punch->photo_path);
        $this->assertArrayNotHasKey('photo', $punch->raw_payload ?? []);

        $this->actingAs($user)
            ->get(route('organization.timesheet.photo', [$organization->slug, $person, $punch]))
            ->assertOk();
    }

    public function test_offline_is_rejected_when_location_disallows_it(): void
    {
        [$user, , $person, $location] = $this->seedAtLocation();
        $location->update(['allow_offline' => false]);
        $person->refresh();

        $this->expectException(ValidationException::class);

        app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Pwa->value,
            'offline' => true,
        ]);
    }

    public function test_device_bind_strict_locks_to_first_device(): void
    {
        [$user, $organization, $person, $location] = $this->seedAtLocation();
        $location->update(['device_bind_mode' => DeviceBindMode::Strict]);
        $person->refresh();

        app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Pwa->value,
            'device_id' => 'uredaj-a',
        ]);

        $this->assertSame('uredaj-a', $person->fresh()->clock_device_id);
        $this->assertSame('pass', Punch::query()->where('person_id', $person->id)->value('device_result'));

        app(ClockService::class)->punch($person->fresh(), $user, [
            'type' => PunchType::Out->value,
            'channel' => ClockChannel::Pwa->value,
            'device_id' => 'uredaj-a',
        ]);

        try {
            app(ClockService::class)->punch($person->fresh(), $user, [
                'type' => PunchType::In->value,
                'channel' => ClockChannel::Pwa->value,
                'device_id' => 'uredaj-b',
            ]);
            $this->fail('Očekivana je ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('device_id', $e->errors());
        }

        $this->actingAs($user)
            ->put(route('organization.people.update', [$organization->slug, $person]), [
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'status' => PersonStatus::Employee->value,
                'clock_device_reset' => '1',
            ])
            ->assertRedirect();

        $this->assertNull($person->fresh()->clock_device_id);
    }

    public function test_device_bind_warn_records_mismatch(): void
    {
        [$user, , $person, $location] = $this->seedAtLocation();
        $location->update(['device_bind_mode' => DeviceBindMode::Warn]);
        $person->refresh();

        app(ClockService::class)->punch($person, $user, [
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Pwa->value,
            'device_id' => 'prvi',
        ]);
        app(ClockService::class)->punch($person->fresh(), $user, [
            'type' => PunchType::Out->value,
            'channel' => ClockChannel::Pwa->value,
            'device_id' => 'drugi',
        ]);

        $out = Punch::query()->where('person_id', $person->id)->where('type', PunchType::Out)->first();
        $this->assertSame('mismatch', $out?->device_result);
        $this->assertTrue($out->deviceMismatch());
    }

    public function test_kiosk_does_not_bind_device(): void
    {
        [$user, , $person, $location] = $this->seedAtLocation();
        $location->update(['device_bind_mode' => DeviceBindMode::Strict, 'kiosk_enabled' => true]);
        $person->update(['clock_pin' => '2222']);

        app(ClockService::class)->punch($person->fresh(), null, [
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Kiosk->value,
            'device_id' => 'kiosk-tablet',
            'location_id' => $location->id,
        ]);

        $this->assertNull($person->fresh()->clock_device_id);
        $this->assertSame('skipped', Punch::query()->where('person_id', $person->id)->value('device_result'));
    }

    public function test_clock_page_asks_for_photo_when_required(): void
    {
        [$user, $organization, $person, $location] = $this->seedAtLocation();
        $location->update(['require_photo' => true]);
        $person->refresh();

        $this->actingAs($user)
            ->get(route('organization.clock', $organization->slug))
            ->assertOk()
            ->assertSee('Fotografija prijave');
    }

    public function test_old_punch_photos_are_purged(): void
    {
        Storage::fake('local');
        [, , $person] = $this->seedAtLocation();
        Storage::disk('local')->put('punch-photos/old.jpg', 'x');
        $punch = Punch::query()->create([
            'organization_id' => $person->organization_id,
            'person_id' => $person->id,
            'type' => PunchType::In,
            'channel' => ClockChannel::Pwa,
            'occurred_at_device' => now()->subDays(40),
            'occurred_at_server' => now()->subDays(40),
            'geofence_result' => 'skipped',
            'offline' => false,
            'client_event_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'integrity_hash' => str_repeat('a', 64),
            'photo_path' => 'punch-photos/old.jpg',
            'photo_taken_at' => now()->subDays(40),
        ]);

        $this->assertSame(1, app(RetentionService::class)->purgePunchPhotos(30));
        $this->assertNull($punch->fresh()->photo_path);
        Storage::disk('local')->assertMissing('punch-photos/old.jpg');
    }

    /**
     * @return array{0: User, 1: Organization, 2: Person, 3: Location}
     */
    private function seedAtLocation(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Zona d.o.o.',
            'slug' => 'zona-'.uniqid(),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);
        $location = Location::factory()->create([
            'organization_id' => $organization->id,
            'allow_offline' => true,
            'require_photo' => false,
            'device_bind_mode' => DeviceBindMode::Off,
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => PersonStatus::Employee,
            'location_id' => $location->id,
        ]);

        return [$user, $organization, $person, $location];
    }
}
