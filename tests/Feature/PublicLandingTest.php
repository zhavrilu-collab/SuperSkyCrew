<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Support\OrganizationFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_superskycrew_landing(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('SuperSkyCrew')
            ->assertSee('Prijava')
            ->assertSee('Registracija tvrtke')
            ->assertSee('Cijene')
            ->assertSee('14 dana');
    }

    public function test_pricing_lists_plans_and_links_to_registration(): void
    {
        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Osnovni')
            ->assertSee('Standardni')
            ->assertSee('Premium')
            ->assertSee(OrganizationFeatures::planSummary('basic'));

        $this->get(route('register.organization', ['plan' => 'premium']))
            ->assertOk()
            ->assertSee('value="premium"', false);
    }

    public function test_home_sends_logged_in_owner_to_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('organization.landing', $organization->slug));
    }
}
