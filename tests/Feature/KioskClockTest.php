<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
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
use App\Services\ClockQrService;
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
            ->assertSee('PIN ili QR')
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
            ->assertSee('PIN ili QR')
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

    public function test_guest_can_identify_with_person_qr(): void
    {
        [$organization, $location, $person] = $this->seedKiosk();
        $payload = app(ClockQrService::class)->payload($person);

        $this->post(route('organization.kiosk.identify', [$organization->slug, $location->kiosk_token]), [
            'qr' => $payload,
        ])
            ->assertRedirect(route('organization.kiosk', [$organization->slug, $location->kiosk_token]));

        $this->get(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->assertOk()
            ->assertSee($person->fullName());
    }

    public function test_raw_clock_qr_token_identifies_person(): void
    {
        [$organization, $location, $person] = $this->seedKiosk();

        $this->post(route('organization.kiosk.identify', [$organization->slug, $location->kiosk_token]), [
            'qr' => $person->clock_qr,
        ])
            ->assertRedirect(route('organization.kiosk', [$organization->slug, $location->kiosk_token]));

        $this->get(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->assertOk()
            ->assertSee('Ivan Horvat');
    }

    public function test_unknown_qr_is_rejected(): void
    {
        [$organization, $location] = $this->seedKiosk();

        $this->from(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->post(route('organization.kiosk.identify', [$organization->slug, $location->kiosk_token]), [
                'qr' => 'hr1:kiosk-firma:aaaaaaaaaaaaaaaa',
            ])
            ->assertSessionHasErrors('qr');
    }

    public function test_qr_from_another_tenant_is_rejected(): void
    {
        [$organization, $location, $person] = $this->seedKiosk();

        $this->from(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->post(route('organization.kiosk.identify', [$organization->slug, $location->kiosk_token]), [
                'qr' => 'hr1:tuđa-tvrtka:'.$person->clock_qr,
            ])
            ->assertSessionHasErrors('qr');
    }

    public function test_owner_can_print_badge_and_rotate_qr(): void
    {
        [$organization, $location, $person] = $this->seedKiosk();
        [$owner] = $this->seedOwner($organization);
        $old = $person->clock_qr;

        $this->actingAs($owner)
            ->get(route('organization.people.badge', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('Iskaznica za kiosk')
            ->assertSee($person->fullName())
            ->assertSee('hr1:kiosk-firma:'.$old, false);

        $this->actingAs($owner)
            ->post(route('organization.people.qr.rotate', [$organization->slug, $person]))
            ->assertRedirect(route('organization.people.badge', [$organization->slug, $person]));

        $person->refresh();
        $this->assertNotSame($old, $person->clock_qr);

        $this->from(route('organization.kiosk', [$organization->slug, $location->kiosk_token]))
            ->post(route('organization.kiosk.identify', [$organization->slug, $location->kiosk_token]), [
                'qr' => 'hr1:kiosk-firma:'.$old,
            ])
            ->assertSessionHasErrors('qr');
    }

    public function test_owner_can_print_location_kiosk_qr(): void
    {
        [$organization, $location] = $this->seedKiosk();
        [$owner] = $this->seedOwner($organization);
        $url = $location->kioskUrl();

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'vrijeme',
                'section' => 'lokacije',
            ]))
            ->assertOk()
            ->assertSee('QR za tablet')
            ->assertSee('Otvori kiosk');

        $this->actingAs($owner)
            ->get(route('organization.settings.locations.kiosk-qr', [$organization->slug, $location]))
            ->assertOk()
            ->assertSee($location->name)
            ->assertSee($url, false);
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

    /**
     * @return array{0: User}
     */
    private function seedOwner(Organization $organization): array
    {
        $user = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$user];
    }
}
