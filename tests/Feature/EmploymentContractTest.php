<?php

namespace Tests\Feature;

use App\Enums\ContractType;
use App\Enums\EmploymentInstrument;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\EmploymentContract;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmploymentContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_contract_and_see_it_on_review(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Babić',
            'status' => PersonStatus::Employee,
            'started_at' => null,
            'contract_type' => null,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'ugovori']))
            ->assertOk()
            ->assertSee('Ugovori i aneksi');

        $this->actingAs($owner)
            ->post(route('organization.contracts.store', [$organization->slug, $person]), [
                'kind' => EmploymentInstrument::EmploymentContract->value,
                'contract_type' => ContractType::Indefinite->value,
                'signed_at' => '2024-01-15',
                'starts_at' => '2024-01-15',
                'weekly_hours' => 40,
                'is_current' => '1',
            ])
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'ugovori']));

        $person->refresh();
        $this->assertSame(ContractType::Indefinite, $person->contract_type);
        $this->assertSame('2024-01-15', $person->started_at->toDateString());
        $this->assertDatabaseHas('employment_contracts', [
            'person_id' => $person->id,
            'kind' => EmploymentInstrument::EmploymentContract->value,
            'is_current' => 1,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.review', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('Ugovor o radu')
            ->assertSee('važeći');
    }

    public function test_annex_becomes_current_and_prints(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Petra',
            'last_name' => 'Novak',
            'status' => PersonStatus::Employee,
            'job_title' => 'Referentica',
            'contract_type' => ContractType::Indefinite,
        ]);

        $this->actingAs($owner)
            ->post(route('organization.contracts.store', [$organization->slug, $person]), [
                'kind' => EmploymentInstrument::EmploymentContract->value,
                'contract_type' => ContractType::Indefinite->value,
                'number' => 'UOR-1/2024',
                'signed_at' => '2024-01-01',
                'starts_at' => '2024-01-01',
                'is_current' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('organization.contracts.store', [$organization->slug, $person]), [
                'kind' => EmploymentInstrument::Annex->value,
                'contract_type' => ContractType::PartTime->value,
                'number' => 'AN-2/2026',
                'signed_at' => now()->toDateString(),
                'starts_at' => now()->toDateString(),
                'weekly_hours' => 20,
                'is_current' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(1, EmploymentContract::query()->where('person_id', $person->id)->where('is_current', true)->count());
        $this->assertSame(ContractType::PartTime, $person->fresh()->contract_type);

        $this->actingAs($owner)
            ->get(route('organization.people.contract', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('ANEKS UGOVORA O RADU')
            ->assertSee('AN-2/2026')
            ->assertSee('20 sati tjedno');
    }

    public function test_fixed_term_and_trial_show_on_expiry_list(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Lana',
            'last_name' => 'Kovač',
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($owner)
            ->post(route('organization.contracts.store', [$organization->slug, $person]), [
                'kind' => EmploymentInstrument::EmploymentContract->value,
                'contract_type' => ContractType::FixedTerm->value,
                'signed_at' => now()->subMonth()->toDateString(),
                'starts_at' => now()->subMonth()->toDateString(),
                'ends_at' => now()->addDays(12)->toDateString(),
                'trial_ends_at' => now()->addDays(6)->toDateString(),
                'is_current' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('organization.expiries.index', $organization->slug))
            ->assertOk()
            ->assertSee('Lana Kovač')
            ->assertSee('UOR na određeno')
            ->assertSee('Probni rad');
    }

    public function test_fixed_term_requires_end_date(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($owner)
            ->from(route('organization.people.edit', [$organization->slug, $person]))
            ->post(route('organization.contracts.store', [$organization->slug, $person]), [
                'kind' => EmploymentInstrument::EmploymentContract->value,
                'contract_type' => ContractType::FixedTerm->value,
                'signed_at' => now()->toDateString(),
                'starts_at' => now()->toDateString(),
                'is_current' => '1',
            ])
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $person]))
            ->assertSessionHasErrors('ends_at');
    }

    public function test_employee_cannot_add_contract(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($employee)
            ->post(route('organization.contracts.store', [$organization->slug, $person]), [
                'kind' => EmploymentInstrument::EmploymentContract->value,
                'signed_at' => now()->toDateString(),
                'starts_at' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_owner_can_remove_contract(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);
        $item = EmploymentContract::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'kind' => EmploymentInstrument::EmploymentContract,
            'signed_at' => now()->toDateString(),
            'starts_at' => now()->toDateString(),
            'is_current' => true,
        ]);

        $this->actingAs($owner)
            ->delete(route('organization.contracts.destroy', [$organization->slug, $person, $item]))
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'ugovori']));

        $this->assertDatabaseMissing('employment_contracts', ['id' => $item->id]);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Ugovori d.o.o.',
            'slug' => 'ugovori-'.uniqid(),
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
