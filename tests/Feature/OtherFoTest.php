<?php

namespace Tests\Feature;

use App\Enums\OtherFoKind;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\EmploymentContract;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use App\Services\EmploymentContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtherFoTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_other_fo_and_print_article_ten(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->get(route('organization.people.create', $organization->slug))
            ->assertOk()
            ->assertSee('Druge FO i honorarci');

        $response = $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Luka',
                'last_name' => 'Student',
                'status' => PersonStatus::OtherFo->value,
                'fo_kind' => OtherFoKind::Student->value,
                'instrument_title' => 'Ugovor o obavljanju studentskih poslova',
                'job_title' => 'Pomoćni referent',
                'started_at' => '2026-07-01',
                'citizenship' => 'HR',
            ]);
        $person = Person::query()->where('last_name', 'Student')->first();
        $this->assertNotNull($person);
        $response->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'pregled']));
        $this->assertSame(PersonStatus::OtherFo, $person->status);
        $this->assertSame(OtherFoKind::Student, $person->fo_kind);

        $this->actingAs($owner)
            ->get(route('organization.people.index', [$organization->slug, 'status' => PersonStatus::OtherFo->value]))
            ->assertOk()
            ->assertSee('Luka Student')
            ->assertSee('Student');

        $this->actingAs($owner)
            ->get(route('organization.people.review', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('čl. 10.')
            ->assertSee('Ugovor o obavljanju studentskih poslova')
            ->assertDontSee('čl. 4.');

        $this->actingAs($owner)
            ->get(route('organization.people.article-ten', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('drugim fizičkim osobama')
            ->assertSee('Luka Student');
    }

    public function test_other_fo_requires_kind_and_instrument(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->from(route('organization.people.create', $organization->slug))
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Ana',
                'last_name' => 'FO',
                'status' => PersonStatus::OtherFo->value,
            ])
            ->assertRedirect(route('organization.people.create', $organization->slug))
            ->assertSessionHasErrors(['fo_kind', 'instrument_title']);
    }

    public function test_contractor_requires_instrument_title(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $response = $this->actingAs($owner)
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Iva',
                'last_name' => 'Honor',
                'status' => PersonStatus::Contractor->value,
                'instrument_title' => 'Ugovor o djelu — prijevod',
                'job_title' => 'Prevodioc',
            ]);
        $contractor = Person::query()->where('last_name', 'Honor')->first();
        $this->assertNotNull($contractor);
        $response->assertRedirect(route('organization.people.edit', [$organization->slug, $contractor, 'tab' => 'pregled']));

        $this->actingAs($owner)
            ->from(route('organization.people.create', $organization->slug))
            ->post(route('organization.people.store', $organization->slug), [
                'first_name' => 'Marko',
                'last_name' => 'BezAkta',
                'status' => PersonStatus::Contractor->value,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('instrument_title');

        $contractor = Person::query()->where('last_name', 'Honor')->first();
        $this->actingAs($owner)
            ->get(route('organization.people.review', [$organization->slug, $contractor]))
            ->assertOk()
            ->assertSee('Kartica honorarca')
            ->assertSee('Ugovor o djelu — prijevod')
            ->assertDontSee('Pisani pregled evidencije o radniku')
            ->assertDontSee('čl. 4.');

        $this->actingAs($owner)
            ->get(route('organization.people.article-ten', [$organization->slug, $contractor]))
            ->assertNotFound();
    }

    public function test_employee_review_stays_article_four_and_article_ten_is_404(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Lana',
            'last_name' => 'Matić',
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.review', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('čl. 4.');

        $this->actingAs($owner)
            ->get(route('organization.people.article-ten', [$organization->slug, $person]))
            ->assertNotFound();
    }

    public function test_seed_if_missing_skips_other_fo(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::OtherFo,
            'fo_kind' => OtherFoKind::Sor,
            'instrument_title' => 'Sporazum o radu',
        ]);

        $created = app(EmploymentContractService::class)->seedIfMissing($person, $owner);
        $this->assertNull($created);
        $this->assertSame(0, EmploymentContract::query()->where('person_id', $person->id)->count());
    }

    public function test_employee_cannot_open_other_fo_card(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::OtherFo,
            'fo_kind' => OtherFoKind::Student,
            'instrument_title' => 'Studentski ugovor',
        ]);

        $this->actingAs($employee)
            ->get(route('organization.people.review', [$organization->slug, $person]))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'FO d.o.o.',
            'slug' => 'fo-'.uniqid(),
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
