<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Support\OrganizationThemes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationUiChromeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_shell_and_settings(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->assertSame('tirkizna', $organization->theme_key);
        $this->assertSame('tirkizna', OrganizationThemes::DEFAULT);

        $this->actingAs($owner)
            ->get(route('organization.landing', $organization->slug))
            ->assertRedirect(route('organization.dashboard', $organization->slug));

        $dashboard = $this->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('app-sidebar', false)
            ->assertSee('app-topbar', false)
            ->assertSee('SuperSkyCrew')
            ->assertSee('app-sidebar-mark', false)
            ->assertSee('nav-home', false)
            ->assertSee('nav-clipboard', false)
            ->assertSee('nav-buildings', false)
            ->assertSee('nav-contract', false)
            ->assertSee('nav-timesheet', false)
            ->assertSee('nav-palette', false)
            ->assertSee('Nadzorna ploča')
            ->assertSee('Profil tvrtke')
            ->assertSee('Ustroj tvrtke')
            ->assertSee('Sistematizacija')
            ->assertSee('Zaposlenici')
            ->assertSee('Članice grupacije')
            ->assertSee('Poslovnice')
            ->assertSee('Osnovni podaci')
            ->assertSee('Dosjei zaposlenika')
            ->assertSee('Opisi radnih mjesta')
            ->assertSee('Poslovni segmenti')
            ->assertSee('Ugovori o radu')
            ->assertSee('Postavke')
            ->assertSee('Prijavljeni korisnik');

        preg_match_all('/href="#nav-([a-z0-9-]+)"/', $dashboard->getContent(), $iconMatches);
        foreach (array_count_values($iconMatches[1]) as $icon => $count) {
            $this->assertSame(1, $count, "Ikona {$icon} se ponavlja {$count} puta.");
        }

        $dashboard
            ->assertSee('#0f6b64', false)
            ->assertDontSee('odaberite modul');

        $this->actingAs($owner)
            ->get(route('organization.settings.index', $organization->slug))
            ->assertOk()
            ->assertSee('TEMA IZGLEDA')
            ->assertDontSee('role="tablist"', false);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'kadar',
                'section' => 'vrste-dokumenata',
            ]))
            ->assertOk()
            ->assertSee('Vrste dokumenata')
            ->assertSee('Šifrarnik sati')
            ->assertDontSee('role="tablist"', false)
            ->assertDontSee('settings-subnav mb-4', false);

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

    public function test_employee_does_not_see_staff_modules_in_shell(): void
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
            ->assertRedirect(route('organization.dashboard', $organization->slug));

        $this->actingAs($employee)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('Moje')
            ->assertDontSee('Profil tvrtke')
            ->assertDontSee('Ustroj tvrtke')
            ->assertDontSee('Sistematizacija')
            ->assertDontSee('Zaposlenici')
            ->assertDontSee('Dosjei zaposlenika')
            ->assertDontSee('Evidencija');

        $this->actingAs($employee)
            ->get(route('organization.settings.index', $organization->slug))
            ->assertForbidden();
    }

    public function test_owner_opens_kadrovski_modules_and_employee_cannot(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.segments.index', $organization->slug))
            ->assertOk()
            ->assertSee('Poslovni segmenti')
            ->assertDontSee('Uskoro');

        $this->actingAs($owner)
            ->get(route('organization.contracts.index', $organization->slug))
            ->assertOk()
            ->assertSee('Ugovori o radu')
            ->assertDontSee('Uskoro');

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'organizacija',
                'section' => 'osnovni-podaci',
            ]))
            ->assertOk()
            ->assertSee('Osnovni podaci')
            ->assertSee('Naziv organizacije')
            ->assertSee('MBS');

        $this->actingAs($employee)
            ->get(route('organization.segments.index', $organization->slug))
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
