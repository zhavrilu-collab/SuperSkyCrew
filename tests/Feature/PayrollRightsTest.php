<?php

namespace Tests\Feature;

use App\Enums\FamilyRight;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollRightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_store_payroll_fields_on_card_and_review(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Babić',
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.edit', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('Podaci za plaće i prava');

        $this->actingAs($owner)
            ->put(route('organization.people.update', [$organization->slug, $person]), [
                'first_name' => 'Iva',
                'last_name' => 'Babić',
                'status' => PersonStatus::Employee->value,
                'iban' => 'hr12 1001 0051 8630 0016 0',
                'pay_coefficient' => '1.2500',
                'allowance_percent' => '8.5',
                'prior_service_months' => 24,
                'children_count' => 2,
                'dependents_count' => 2,
                'tax_relief_note' => '2 djece',
                'family_right' => FamilyRight::Parental->value,
                'znr_exam_required' => '1',
            ])
            ->assertRedirect(route('organization.people.index', $organization->slug));

        $this->assertDatabaseHas('people', [
            'id' => $person->id,
            'iban' => 'HR1210010051863000160',
            'children_count' => 2,
            'family_right' => FamilyRight::Parental->value,
            'znr_exam_required' => 1,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.review', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('HR1210010051863000160')
            ->assertSee('2 djece')
            ->assertSee('Roditeljska')
            ->assertSee('ZNR obavezan');
    }

    public function test_accountant_can_export_payroll_master_but_not_edit_card(): void
    {
        [$accountant, $organization] = $this->seedMember(OrganizationRole::Accountant);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Lana',
            'last_name' => 'Matić',
            'status' => PersonStatus::Employee,
            'iban' => 'HR1210010051863000160',
            'pay_coefficient' => 1.1,
            'tax_relief_note' => 'osnovni odbitak',
        ]);

        $this->actingAs($accountant)
            ->get(route('organization.people.payroll', $organization->slug))
            ->assertOk()
            ->assertSee('Podaci za plaće')
            ->assertSee('Lana Matić')
            ->assertSee('HR1210010051863000160')
            ->assertSee('osnovni odbitak')
            ->assertDontSee('>Kartica<');

        $csv = $this->actingAs($accountant)
            ->get(route('organization.people.payroll-export', $organization->slug))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('IBAN', $csv);
        $this->assertStringContainsString('HR1210010051863000160', $csv);
        $this->assertStringContainsString('osnovni odbitak', $csv);

        $this->actingAs($accountant)
            ->get(route('organization.people.edit', [$organization->slug, $person]))
            ->assertForbidden();

        $this->actingAs($accountant)
            ->put(route('organization.people.update', [$organization->slug, $person]), [
                'first_name' => 'Lana',
                'last_name' => 'Matić',
                'status' => PersonStatus::Employee->value,
                'iban' => 'HR1210010051863000160',
            ])
            ->assertForbidden();
    }

    public function test_manager_and_employee_cannot_open_payroll_list(): void
    {
        [$manager, $organization] = $this->seedMember(OrganizationRole::Manager);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($manager)
            ->get(route('organization.people.payroll', $organization->slug))
            ->assertForbidden();

        [$employee, $org] = $this->seedMember(OrganizationRole::Employee, 'place-radnik');
        $this->actingAs($employee)
            ->get(route('organization.people.payroll', $org->slug))
            ->assertForbidden();
    }

    public function test_invalid_iban_is_rejected(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($owner)
            ->from(route('organization.people.edit', [$organization->slug, $person]))
            ->put(route('organization.people.update', [$organization->slug, $person]), [
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'status' => PersonStatus::Employee->value,
                'iban' => '123',
            ])
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $person]))
            ->assertSessionHasErrors('iban');
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role, string $slug = 'place-firma'): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Place d.o.o.',
            'slug' => $slug.'-'.uniqid(),
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
