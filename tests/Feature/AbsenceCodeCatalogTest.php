<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\AbsenceCode;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\HrSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsenceCodeCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_seeded_codes_with_written_meaning(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        app(HrSetupService::class)->provision($organization);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'vrijeme',
                'section' => 'sifarnik',
            ]))
            ->assertOk()
            ->assertSee('pisano značenje')
            ->assertSee('Prisutnost')
            ->assertSee('Godišnji odmor')
            ->assertSee('Troši fond GO')
            ->assertSee('Terenski rad')
            ->assertDontSee('>presence<', false);
    }

    public function test_owner_can_add_custom_code_and_cannot_delete_system(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        app(HrSetupService::class)->provision($organization);

        $this->actingAs($owner)
            ->post(route('organization.settings.codes.store', $organization->slug), [
                'code' => 'tr',
                'name' => 'Edukacija',
                'meaning' => 'Sati stručnog usavršavanja izvan radnog mjesta.',
                'category' => 'leave',
                'paid' => '1',
            ])
            ->assertRedirect();

        $custom = AbsenceCode::query()->where('code', 'TR')->first();
        $this->assertNotNull($custom);
        $this->assertFalse($custom->is_system);
        $this->assertSame('absence', $custom->kind);

        $this->actingAs($owner)
            ->from(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'vrijeme',
                'section' => 'sifarnik',
            ]))
            ->post(route('organization.settings.codes.store', $organization->slug), [
                'code' => 'GO',
                'name' => 'Duplikat',
                'meaning' => 'Ne smije proći.',
                'category' => 'leave',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('code');

        $system = AbsenceCode::query()->where('code', 'GO')->first();
        $this->actingAs($owner)
            ->delete(route('organization.settings.codes.destroy', [$organization->slug, $system]))
            ->assertStatus(422);

        $this->actingAs($owner)
            ->delete(route('organization.settings.codes.destroy', [$organization->slug, $custom]))
            ->assertRedirect();
        $this->assertNull(AbsenceCode::query()->find($custom->id));
    }

    public function test_custom_code_requires_meaning_and_employee_cannot_manage(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);

        $this->actingAs($owner)
            ->from(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'vrijeme',
                'section' => 'sifarnik',
            ]))
            ->post(route('organization.settings.codes.store', $organization->slug), [
                'code' => 'XX',
                'name' => 'Bez značenja',
                'category' => 'other',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('meaning');

        $this->actingAs($employee)
            ->post(route('organization.settings.codes.store', $organization->slug), [
                'code' => 'XX',
                'name' => 'Zabranjeno',
                'meaning' => 'Ne smije.',
                'category' => 'other',
            ])
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Šifre d.o.o.',
            'slug' => 'sifre-'.uniqid(),
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
