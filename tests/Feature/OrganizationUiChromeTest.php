<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationUiChromeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_landing_and_settings(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->get(route('organization.landing', $organization->slug))
            ->assertOk()
            ->assertSee('odaberite modul')
            ->assertSee('Postavke')
            ->assertSee('Kadrovi')
            ->assertSee('👥')
            ->assertSee('⚙')
            ->assertSee('top-bar', false)
            ->assertSee('Prijavljeni korisnik')
            ->assertDontSee('navModulesMenuBtn', false);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'organizacija',
                'section' => 'izgled',
            ]))
            ->assertOk()
            ->assertSee('Izgled')
            ->assertSee('TEMA IZGLEDA')
            ->assertSee('theme-preview.js', false)
            ->assertSee('data-tema="plava"', false)
            ->assertSee('Spremi temu');

        $this->actingAs($owner)
            ->put(route('organization.settings.theme', $organization->slug), [
                'theme_key' => 'plava',
            ])
            ->assertRedirect();

        $this->assertSame('plava', $organization->fresh()->theme_key);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'organizacija',
                'section' => 'izgled',
            ]))
            ->assertOk()
            ->assertSee('#1b3a5c');
    }

    public function test_employee_does_not_see_staff_modules_on_landing(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);

        $this->actingAs($employee)
            ->get(route('organization.landing', $organization->slug))
            ->assertOk()
            ->assertSee('Moje')
            ->assertDontSee('Evidencija osoba prema Pravilniku')
            ->assertDontSee('Izgled, ustroj i konfiguracija');

        $this->actingAs($employee)
            ->get(route('organization.settings.index', $organization->slug))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Aktivna d.o.o.',
            'slug' => 'aktivna-ui',
            'status' => OrganizationStatus::Active,
            'plan' => 'basic',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $organization];
    }
}
