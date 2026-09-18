<?php

namespace Tests\Feature;

use App\Enums\ClockChannel;
use App\Enums\ExceptionCode;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ClockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExceptionQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_sees_open_exceptions_and_can_resolve(): void
    {
        [$manager, $organization] = $this->seedMember(OrganizationRole::Manager);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Babić',
            'status' => PersonStatus::Employee,
        ]);

        $yesterday = now()->timezone(config('app.timezone'))->subDay()->startOfDay();
        app(ClockService::class)->punch($person, $manager, [
            'type' => PunchType::In->value,
            'occurred_at' => $yesterday->copy()->addHours(8)->toDateTimeString(),
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Zaboravljena prijava',
        ]);

        $this->actingAs($manager)
            ->get(route('organization.exceptions.index', $organization->slug))
            ->assertOk()
            ->assertSee('Queue iznimki')
            ->assertSee('Iva Babić')
            ->assertSee('Nedostaje odjava');

        $entry = TimeEntry::query()->where('person_id', $person->id)->first();

        $this->actingAs($manager)
            ->from(route('organization.exceptions.index', $organization->slug))
            ->post(route('organization.exceptions.resolve', [$organization->slug, $entry]), [
                'comment' => 'Radnica je javila da je ostala na terenu.',
            ])
            ->assertRedirect();

        $this->assertNotNull($entry->fresh()->exception_resolved_at);
        $this->assertSame('Radnica je javila da je ostala na terenu.', $entry->fresh()->exception_note);

        $this->actingAs($manager)
            ->get(route('organization.exceptions.index', $organization->slug))
            ->assertOk()
            ->assertDontSee('Iva Babić');

        $this->actingAs($manager)
            ->get(route('organization.exceptions.index', [$organization->slug, 'resolved' => 1]))
            ->assertOk()
            ->assertSee('Iva Babić')
            ->assertSee('Radnica je javila da je ostala na terenu.');
    }

    public function test_todays_open_clock_in_is_not_in_the_queue(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Marko',
            'last_name' => 'NaPoslu',
            'status' => PersonStatus::Employee,
        ]);

        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::In->value,
            'occurred_at' => now()->timezone(config('app.timezone'))->startOfDay()->addHours(8)->toDateTimeString(),
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Test',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.exceptions.index', $organization->slug))
            ->assertOk()
            ->assertDontSee('Marko NaPoslu');

        $this->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('Iznimke')
            ->assertSee('Nema otvorenih');
    }

    public function test_employee_cannot_open_queue_and_accountant_cannot_resolve(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);
        $this->actingAs($employee)
            ->get(route('organization.exceptions.index', $organization->slug))
            ->assertForbidden();

        $accountant = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $accountant->id,
            'role' => OrganizationRole::Accountant,
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Petra',
            'last_name' => 'Iznimka',
            'status' => PersonStatus::Employee,
        ]);
        $yesterday = now()->timezone(config('app.timezone'))->subDay()->startOfDay();
        app(ClockService::class)->punch($person, $accountant, [
            'type' => PunchType::In->value,
            'occurred_at' => $yesterday->copy()->addHours(8)->toDateTimeString(),
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Test',
        ]);
        $entry = TimeEntry::query()->where('person_id', $person->id)->first();

        $this->actingAs($accountant)
            ->get(route('organization.exceptions.index', $organization->slug))
            ->assertOk()
            ->assertSee('Petra Iznimka');

        $this->actingAs($accountant)
            ->post(route('organization.exceptions.resolve', [$organization->slug, $entry]), [
                'comment' => 'Ne smije',
            ])
            ->assertForbidden();
    }

    public function test_resolve_requires_a_note(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);
        $yesterday = now()->timezone(config('app.timezone'))->subDay()->startOfDay();
        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::In->value,
            'occurred_at' => $yesterday->copy()->addHours(8)->toDateTimeString(),
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Test',
        ]);
        $entry = TimeEntry::query()->where('person_id', $person->id)->first();

        $this->actingAs($owner)
            ->from(route('organization.exceptions.index', $organization->slug))
            ->post(route('organization.exceptions.resolve', [$organization->slug, $entry]))
            ->assertSessionHasErrors('comment');
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Iznimke d.o.o.',
            'slug' => 'iznimke-firma-'.uniqid(),
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
