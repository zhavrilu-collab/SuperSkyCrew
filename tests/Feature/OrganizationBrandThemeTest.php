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

class OrganizationBrandThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_theme_is_green(): void
    {
        $this->assertSame('zelena', OrganizationThemes::DEFAULT);
        $this->assertSame(
            ['zelena', 'plava', 'crvena', 'zuta', 'narancasta'],
            OrganizationThemes::keys(),
        );
        $this->assertSame('zelena', OrganizationThemes::resolve(null));
        $this->assertSame('zelena', OrganizationThemes::resolve('tirkizna'));

        $organization = Organization::factory()->create();
        $this->assertSame('zelena', $organization->theme_key);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('brand/product/zelena.png', false)
            ->assertDontSee('zelena-horizontal.png', false);

        $this->get(route('register.organization'))
            ->assertOk()
            ->assertSee('brand/product/zelena.png', false)
            ->assertDontSee('zelena-horizontal.png', false);
    }

    public function test_disallowed_theme_is_rejected(): void
    {
        [$owner, $organization] = $this->seedOwner();

        $this->actingAs($owner)
            ->from(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'organizacija',
                'section' => 'izgled',
            ]))
            ->put(route('organization.settings.theme', $organization->slug), [
                'theme_key' => 'tirkizna',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('theme_key');

        $this->assertSame('zelena', $organization->fresh()->theme_key);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'organizacija',
                'section' => 'izgled',
            ]))
            ->assertOk()
            ->assertSee('data-tema="zelena"', false)
            ->assertSee('data-tema="zuta"', false)
            ->assertDontSee('data-tema="tirkizna"', false)
            ->assertDontSee('data-tema="bordo"', false);
    }

    public function test_selected_theme_uses_its_horizontal_logo(): void
    {
        [$owner, $organization] = $this->seedOwner();

        $path = OrganizationThemes::productLogoPath('crvena', true);
        $this->assertFileExists(public_path($path));

        $this->actingAs($owner)
            ->put(route('organization.settings.theme', $organization->slug), [
                'theme_key' => 'crvena',
            ])
            ->assertRedirect();

        $this->assertSame('crvena', $organization->fresh()->theme_key);

        $this->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('brand/product/crvena-horizontal.png', false)
            ->assertDontSee('brand/product/zelena-horizontal.png', false);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'organizacija',
                'section' => 'izgled',
            ]))
            ->assertOk()
            ->assertSee('brand/product/crvena-horizontal.png', false);
    }

    public function test_theme_style_is_saved_and_unknown_style_is_rejected(): void
    {
        [$owner, $organization] = $this->seedOwner();

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'organizacija',
                'section' => 'izgled',
            ]))
            ->assertOk()
            ->assertSee('data-stil="kreda"', false)
            ->assertSee('data-stil="obrub"', false)
            ->assertSee('data-stil="pruga"', false)
            ->assertSee('data-stil="sjena"', false)
            ->assertSee('data-stil="slovo"', false)
            ->assertSee('data-stil="noc"', false)
            ->assertSee('Početna', false)
            ->assertDontSee('data-stil="tiha"', false)
            ->assertDontSee('data-stil="obrnuto"', false);

        $this->actingAs($owner)
            ->put(route('organization.settings.theme', $organization->slug), [
                'theme_key' => 'zelena',
                'theme_style' => 'kreda',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('kreda', $organization->fresh()->theme_style);

        $this->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('#5b651f', false)
            ->assertSee('brand/product/zelena-horizontal.png', false);

        $this->actingAs($owner)
            ->from(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'organizacija',
                'section' => 'izgled',
            ]))
            ->put(route('organization.settings.theme', $organization->slug), [
                'theme_key' => 'zelena',
                'theme_style' => 'obrnuto',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('theme_style');

        $this->assertSame('kreda', $organization->fresh()->theme_style);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedOwner(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'status' => OrganizationStatus::Active,
            'theme_key' => 'zelena',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$user, $organization];
    }
}
