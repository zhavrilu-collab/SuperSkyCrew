<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Punch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KioskClockTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_identify_with_pin_and_clock_in(): void
    {
        [$organization, $location, $person] = $this->seedKiosk();

        $this->get(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->assertOk()
            ->assertSee('Unesite PIN')
            ->assertDontSee($person->fullName());

        $this->post(route('organization.kiosk.identify', [$organization->slug, $location->kiosk_token]), [
            'pin' => '2222',
        ])
            ->assertRedirect(route('organization.kiosk', [$organization->slug, $location->kiosk_token]));

        $this->get(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->assertOk()
            ->assertSee($person->fullName())
            ->assertSee('Prijava');

        $this->post(route('organization.kiosk.punch', [$organization->slug, $location->kiosk_token]), [
            'type' => PunchType::In->value,
            'client_event_id' => '66666666-6666-4666-8666-666666666666',
        ])
            ->assertRedirect(route('organization.kiosk', [$organization->slug, $location->kiosk_token]));

        $this->assertDatabaseHas('punches', [
            'person_id' => $person->id,
            'location_id' => $location->id,
            'type' => PunchType::In->value,
            'channel' => ClockChannel::Kiosk->value,
            'created_by_user_id' => null,
        ]);

        $this->get(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->assertOk()
            ->assertSee('Unesite PIN')
            ->assertSee('Prijava');
    }

    public function test_wrong_pin_is_rejected(): void
    {
        [$organization, $location] = $this->seedKiosk();

        $this->from(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->post(route('organization.kiosk.identify', [$organization->slug, $location->kiosk_token]), [
                'pin' => '9999',
            ])
            ->assertSessionHasErrors('pin');

        $this->assertSame(0, Punch::query()->count());
    }

    public function test_unknown_or_disabled_kiosk_is_not_found(): void
    {
        [$organization, $location] = $this->seedKiosk();

        $this->get(route('organization.kiosk', [$organization->slug, 'nema-takvog-tokena']))
            ->assertNotFound();

        $location->update(['kiosk_enabled' => false]);

        $this->get(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->assertNotFound();
    }

    public function test_pending_organization_kiosk_is_not_found(): void
    {
        [$organization, $location] = $this->seedKiosk();
        $organization->update(['status' => OrganizationStatus::Pending]);

        $this->get(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->assertNotFound();
    }

    /**
     * @return array{0: Organization, 1: Location, 2: Person}
     */
    private function seedKiosk(): array
    {
        $organization = Organization::query()->create([
            'name' => 'Kiosk d.o.o.',
            'slug' => 'kiosk-firma',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        $location = Location::factory()->create([
            'organization_id' => $organization->id,
            'kiosk_enabled' => true,
            'kiosk_token' => 'kiosk-test-token',
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'location_id' => $location->id,
            'first_name' => 'Ivan',
            'last_name' => 'Horvat',
            'status' => PersonStatus::Employee,
            'clock_pin' => '2222',
        ]);

        return [$organization, $location, $person];
    }
}
