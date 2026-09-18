<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\QualificationKind;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QualificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_qualification_to_card_and_review(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Stručna',
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.edit', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('Obrazovanje i certifikati');

        $this->actingAs($owner)
            ->post(route('organization.qualifications.store', [$organization->slug, $person]), [
                'kind' => QualificationKind::License->value,
                'title' => 'Viljuškar',
                'issuer' => 'HZZ',
                'issued_on' => '2024-03-01',
                'expires_at' => now()->addDays(8)->toDateString(),
                'required_for_job' => '1',
            ])
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $person]));

        $this->assertDatabaseHas('person_qualifications', [
            'person_id' => $person->id,
            'title' => 'Viljuškar',
            'kind' => QualificationKind::License->value,
            'required_for_job' => 1,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.review', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('Viljuškar')
            ->assertSee('uvjet za posao')
            ->assertDontSee('nije uneseno u karticu');

        $this->actingAs($owner)
            ->get(route('organization.expiries.index', $organization->slug))
            ->assertOk()
            ->assertSee('Viljuškar')
            ->assertSee('Obrazovanje / certifikat');

        $this->actingAs($owner)
            ->get(route('organization.people.index', $organization->slug))
            ->assertOk()
            ->assertSee('istek 1');
    }

    public function test_employee_cannot_add_qualification(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($employee)
            ->post(route('organization.qualifications.store', [$organization->slug, $person]), [
                'kind' => QualificationKind::Course->value,
                'title' => 'Excel',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_remove_qualification(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);
        $item = Qualification::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'kind' => QualificationKind::Education,
            'title' => 'Ekonomski fakultet',
        ]);

        $this->actingAs($owner)
            ->delete(route('organization.qualifications.destroy', [$organization->slug, $person, $item]))
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $person]));

        $this->assertDatabaseMissing('person_qualifications', ['id' => $item->id]);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Kvalifikacije d.o.o.',
            'slug' => 'kvalifikacije-'.uniqid(),
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
