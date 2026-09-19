<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use App\Services\ClockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StatusProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_requires_host_employer_and_prints_article_four(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->from(route('organization.people.create', $organization->slug))
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Ante',
                'last_name' => 'Ustup',
                'status' => PersonStatus::Assigned->value,
            ])
            ->assertRedirect(route('organization.people.create', $organization->slug))
            ->assertSessionHasErrors('host_employer');

        $response = $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Ante',
                'last_name' => 'Ustup',
                'status' => PersonStatus::Assigned->value,
                'host_employer' => 'Agencija Plus d.o.o.',
                'assignment_clocks' => '1',
                'job_title' => 'Referent',
            ]);
        $person = Person::query()->where('last_name', 'Ustup')->first();
        $this->assertNotNull($person);
        $response->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'pregled']));
        $this->assertTrue($person->assignment_clocks);
        $this->assertTrue($person->isClockEligible());

        $this->actingAs($owner)
            ->get(route('organization.people.review', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('čl. 4.')
            ->assertSee('Agencija Plus d.o.o.')
            ->assertSee('evidencija RV ugovorena');

        $this->actingAs($owner)
            ->get(route('organization.people.book', $organization->slug))
            ->assertOk()
            ->assertSee('Ustup Ante');

        $csv = $this->actingAs($owner)
            ->get(route('organization.people.export', $organization->slug))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('Ustupitelj', $csv);
        $this->assertStringContainsString('Agencija Plus d.o.o.', $csv);
    }

    public function test_assigned_without_clock_agreement_cannot_punch(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Assigned,
            'host_employer' => 'Agencija X',
            'assignment_clocks' => false,
        ]);

        $this->expectException(ValidationException::class);
        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::In->value,
        ]);
    }

    public function test_executive_with_autonomy_prints_article_four_and_gets_uor(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $response = $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Roko',
                'last_name' => 'Ruk',
                'status' => PersonStatus::Executive->value,
                'executive_autonomy' => '1',
                'job_title' => 'Direktor',
            ]);
        $person = Person::query()->where('last_name', 'Ruk')->first();
        $this->assertNotNull($person);
        $response->assertRedirect();
        $this->assertTrue($person->hasRelaxedTimeRecord());

        $this->actingAs($owner)
            ->get(route('organization.people.review', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('čl. 4.')
            ->assertSee('Ugovorena samostalnost');

        $this->actingAs($owner)
            ->get(route('organization.people.contract', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('Ugovor o radu');
    }

    public function test_volunteer_requires_module_and_is_off_register_and_clock(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->get(route('organization.people.create', $organization->slug))
            ->assertOk()
            ->assertDontSee('>Volonter</option>', false);

        $this->actingAs($owner)
            ->from(route('organization.people.create', $organization->slug))
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Vera',
                'last_name' => 'Volontira',
                'status' => PersonStatus::Volunteer->value,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $organization->update(['volunteer_module' => true]);

        $this->actingAs($owner)
            ->get(route('organization.people.create', $organization->slug))
            ->assertOk()
            ->assertSee('Volonter');

        $response = $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Vera',
                'last_name' => 'Volontira',
                'status' => PersonStatus::Volunteer->value,
                'instrument_title' => 'Ugovor o volonterstvu',
                'job_title' => 'Pomoć u arhivi',
            ]);
        $person = Person::query()->where('last_name', 'Volontira')->first();
        $this->assertNotNull($person);
        $response->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'pregled']));
        $this->assertFalse($person->isClockEligible());

        $this->actingAs($owner)
            ->get(route('organization.people.review', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('Kartica volontera')
            ->assertDontSee('čl. 4.');

        $this->actingAs($owner)
            ->get(route('organization.people.contract', [$organization->slug, $person]))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('organization.people.book', $organization->slug))
            ->assertOk()
            ->assertDontSee('Volontira');

        $this->expectException(ValidationException::class);
        app(ClockService::class)->punch($person, $owner, [
            'type' => PunchType::In->value,
        ]);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Statusi d.o.o.',
            'slug' => 'statusi-'.uniqid(),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'volunteer_module' => false,
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $organization];
    }
}
