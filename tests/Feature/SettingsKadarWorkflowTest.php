<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\RequestType;
use App\Enums\RetentionClass;
use App\Models\DocumentType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use App\Models\Workflow;
use App\Services\HrSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsKadarWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_seeded_document_types_and_can_add_custom(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'kadar',
                'section' => 'vrste-dokumenata',
            ]))
            ->assertOk()
            ->assertSee('Ugovor o radu')
            ->assertSee('Dozvola / boravište')
            ->assertSee('sistemska');

        $this->actingAs($owner)
            ->post(route('organization.settings.document-types.store', $organization->slug), [
                'code' => 'Viza',
                'name' => 'Radna viza',
                'retention_class' => RetentionClass::Other->value,
                'tracks_expiry' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('document_types', [
            'organization_id' => $organization->id,
            'code' => 'viza',
            'name' => 'Radna viza',
            'is_system' => false,
            'tracks_expiry' => true,
        ]);
    }

    public function test_system_document_type_cannot_be_deleted_employee_cannot_manage(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);
        app(HrSetupService::class)->provision($organization);
        $system = DocumentType::query()->where('organization_id', $organization->id)->where('code', 'uor')->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('organization.settings.document-types.destroy', [$organization->slug, $system]))
            ->assertStatus(422);

        $this->actingAs($employee)
            ->post(route('organization.settings.document-types.store', $organization->slug), [
                'code' => 'xx',
                'name' => 'Zabranjeno',
                'retention_class' => RetentionClass::Other->value,
            ])
            ->assertForbidden();
    }

    public function test_person_document_appears_on_card_and_expiry_list(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        app(HrSetupService::class)->provision($organization);
        $type = DocumentType::query()->where('organization_id', $organization->id)->where('code', 'dozvola')->firstOrFail();
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Mila',
            'last_name' => 'Dosje',
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($owner)
            ->post(route('organization.documents.store', [$organization->slug, $person]), [
                'document_type_id' => $type->id,
                'title' => 'D-123',
                'issued_on' => now()->subYear()->toDateString(),
                'expires_on' => now()->addDays(8)->toDateString(),
            ])
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'dokumenti']));

        $this->assertDatabaseHas('person_documents', [
            'person_id' => $person->id,
            'document_type_id' => $type->id,
            'title' => 'D-123',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'dokumenti']))
            ->assertOk()
            ->assertSee('Dokumenti dosjea')
            ->assertSee('D-123');

        $this->actingAs($owner)
            ->get(route('organization.expiries.index', $organization->slug))
            ->assertOk()
            ->assertSee('Mila Dosje')
            ->assertSee('Dozvola / boravište');
    }

    public function test_expiry_horizon_uses_organization_setting(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $organization->update(['expiry_warning_days' => 10]);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Daleko',
            'last_name' => 'Istek',
            'status' => PersonStatus::Employee,
            'medical_expires_at' => now()->addDays(20)->toDateString(),
        ]);

        $this->actingAs($owner)
            ->get(route('organization.expiries.index', $organization->slug))
            ->assertOk()
            ->assertSee('Nema isteka')
            ->assertSee('10 dana');

        $this->actingAs($owner)
            ->put(route('organization.settings.expiry', $organization->slug), [
                'expiry_warning_days' => 30,
            ])
            ->assertRedirect();

        $this->assertSame(30, $organization->fresh()->expiry_warning_days);

        $this->actingAs($owner)
            ->get(route('organization.expiries.index', $organization->slug))
            ->assertOk()
            ->assertSee('Daleko Istek')
            ->assertSee('Liječnički pregled');
    }

    public function test_workflow_steps_are_shown_and_custom_path_is_used(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $worker = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $worker->id,
            'role' => OrganizationRole::Employee,
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $worker->id,
            'status' => PersonStatus::Employee,
            'manager_user_id' => $owner->id,
            'annual_leave_days' => 20,
        ]);
        app(HrSetupService::class)->provision($organization);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'odobrenja',
            ]))
            ->assertOk()
            ->assertSee('Godišnji odmor')
            ->assertSee('do 3 dana')
            ->assertSee('4+ dana');

        $workflow = Workflow::query()
            ->where('organization_id', $organization->id)
            ->where('type', RequestType::LeaveAnnual->value)
            ->firstOrFail();
        $managerStep = $workflow->steps()->where('role', OrganizationRole::Manager->value)->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('organization.settings.workflow-steps.destroy', [$organization->slug, $workflow, $managerStep]))
            ->assertRedirect();

        $this->actingAs($worker)
            ->post(route('organization.requests.store', $organization->slug), [
                'type' => RequestType::LeaveAnnual->value,
                'from' => '2026-09-21',
                'to' => '2026-09-22',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('workflow_requests', [
            'person_id' => $person->id,
            'current_role' => OrganizationRole::Hr->value,
        ]);
    }

    public function test_last_workflow_step_cannot_be_removed(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        app(HrSetupService::class)->provision($organization);
        $workflow = Workflow::query()
            ->where('organization_id', $organization->id)
            ->where('type', RequestType::Overtime->value)
            ->firstOrFail();
        $step = $workflow->steps()->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('organization.settings.workflow-steps.destroy', [$organization->slug, $workflow, $step]))
            ->assertStatus(422);

        $this->assertSame(1, $workflow->steps()->count());
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Postavke d.o.o.',
            'slug' => 'postavke-'.uniqid(),
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
