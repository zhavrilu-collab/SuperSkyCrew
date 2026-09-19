<?php

namespace Tests\Feature;

use App\Enums\DocumentKind;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\DocumentHandover;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_print_article_four_review(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Lana',
            'last_name' => 'Matić',
            'oib' => null,
            'job_title' => 'Referentica',
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.review', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('Pisani pregled')
            ->assertSee('čl. 4.')
            ->assertSee('Lana Matić')
            ->assertSee('Referentica')
            ->assertSee('kartica-kontejner', false)
            ->assertSee('ispis-zaglavlje', false);
    }

    public function test_employee_can_open_own_review_but_not_another(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);
        $own = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'first_name' => 'Ivan',
            'last_name' => 'Horvat',
        ]);
        $other = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Marta',
            'last_name' => 'Kovač',
        ]);

        $this->actingAs($employee)
            ->get(route('organization.people.review', [$organization->slug, $own]))
            ->assertOk()
            ->assertSee('Ivan Horvat');

        $this->actingAs($employee)
            ->get(route('organization.people.review', [$organization->slug, $other]))
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('organization.people.index', $organization->slug))
            ->assertForbidden();
    }

    public function test_owner_records_handover_and_employee_cannot(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.handovers.index', $organization->slug))
            ->assertOk()
            ->assertSee('Predaja ovlaštenoj osobi');

        $this->actingAs($owner)
            ->from(route('organization.handovers.index', $organization->slug))
            ->post(route('organization.handovers.store', $organization->slug), [
                'handed_on' => '2026-09-18',
                'recipient' => 'Inspektorat rada',
                'purpose' => 'Nadzor',
                'document_kind' => DocumentKind::TimeRecord->value,
                'person_id' => $person->id,
                'notes' => 'Slog kolovoz',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('document_handovers', [
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'recipient' => 'Inspektorat rada',
            'purpose' => 'Nadzor',
            'document_kind' => DocumentKind::TimeRecord->value,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.timesheet.inspection', [
                'slug' => $organization->slug,
                'from' => '2026-09-01',
                'to' => '2026-09-30',
            ]))
            ->assertOk()
            ->assertSee('Evidencija predaje')
            ->assertSee('Inspektorat rada');

        $this->actingAs($employee)
            ->get(route('organization.handovers.index', $organization->slug))
            ->assertForbidden();

        $this->actingAs($employee)
            ->post(route('organization.handovers.store', $organization->slug), [
                'handed_on' => '2026-09-18',
                'recipient' => 'Netko',
                'purpose' => 'Ne smije',
                'document_kind' => DocumentKind::WrittenReview->value,
            ])
            ->assertForbidden();

        $this->assertSame(1, DocumentHandover::query()->count());
    }

    public function test_owner_can_print_contract_and_medical_referral(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Petra',
            'last_name' => 'Novak',
            'job_title' => 'Knjigovođa',
            'status' => PersonStatus::Employee,
            'medical_expires_at' => '2026-10-01',
            'annual_leave_days' => 22,
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.contract', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('UGOVOR O RADU')
            ->assertSee('Petra Novak')
            ->assertSee('Knjigovođa')
            ->assertSee('Dokumenti d.o.o.')
            ->assertSee('22');

        $this->actingAs($owner)
            ->get(route('organization.people.referral', [$organization->slug, $person]))
            ->assertOk()
            ->assertSee('UPUTNICA')
            ->assertSee('Petra Novak')
            ->assertSee('01.10.2026.')
            ->assertSee('Knjigovođa');
    }

    public function test_employee_can_open_own_contract_but_not_referral(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);
        $own = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'first_name' => 'Ivan',
            'last_name' => 'Horvat',
        ]);
        $other = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Marta',
            'last_name' => 'Kovač',
        ]);

        $this->actingAs($employee)
            ->get(route('organization.people.contract', [$organization->slug, $own]))
            ->assertOk()
            ->assertSee('UGOVOR O RADU')
            ->assertSee('Ivan Horvat');

        $this->actingAs($employee)
            ->get(route('organization.people.contract', [$organization->slug, $other]))
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('organization.people.referral', [$organization->slug, $own]))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Dokumenti d.o.o.',
            'slug' => 'dokumenti-firma',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'oib' => '12345678903',
        ]);

        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $organization];
    }
}
