<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\RetentionClass;
use App\Models\DocumentType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\PersonDocument;
use App\Models\User;
use App\Services\RetentionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_class_document_is_proposed_after_six_years_and_hr_confirms_disposal(): void
    {
        Storage::fake('local');
        Carbon::setTestNow('2026-09-19');

        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Lana',
            'last_name' => 'Bivša',
            'status' => PersonStatus::Former,
            'ended_at' => '2018-03-01',
        ]);
        $type = DocumentType::query()->create([
            'organization_id' => $organization->id,
            'code' => 'ostalo_test',
            'name' => 'Bilješka',
            'retention_class' => RetentionClass::Other,
            'sort_order' => 90,
        ]);
        $path = 'person-documents/'.$organization->id.'/'.$person->id.'/biljeska.txt';
        Storage::disk('local')->put($path, 'sadrzaj');
        $document = PersonDocument::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'document_type_id' => $type->id,
            'title' => 'Stara bilješka',
            'issued_on' => '2018-01-10',
            'file_path' => $path,
            'original_name' => 'biljeska.txt',
            'mime' => 'text/plain',
        ]);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'kadar',
                'section' => 'zadrzavanje',
            ]))
            ->assertOk()
            ->assertSee('Predloženo za brisanje')
            ->assertSee('Lana Bivša')
            ->assertSee('Stara bilješka')
            ->assertDontSee('Job brisanja nije');

        $document->refresh();
        $this->assertNotNull($document->retention_proposed_at);
        $this->assertSame('2024-01-10', $document->retain_until?->toDateString());

        $this->actingAs($owner)
            ->post(route('organization.settings.retention.confirm', [$organization->slug, $document]))
            ->assertRedirect(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'kadar',
                'section' => 'zadrzavanje',
            ]));

        $document->refresh();
        $this->assertNotNull($document->disposed_at);
        $this->assertSame($owner->id, $document->disposed_by);
        $this->assertNull($document->file_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_contract_is_not_proposed_while_employment_is_open(): void
    {
        Carbon::setTestNow('2026-09-19');
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
            'ended_at' => null,
        ]);
        $type = DocumentType::query()->create([
            'organization_id' => $organization->id,
            'code' => 'uor_test',
            'name' => 'UOR test',
            'retention_class' => RetentionClass::Contract,
            'sort_order' => 10,
        ]);
        PersonDocument::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'document_type_id' => $type->id,
            'title' => 'Ugovor',
            'issued_on' => '2015-01-01',
        ]);

        $this->assertSame(0, app(RetentionService::class)->proposeDue($organization));
        $this->assertDatabaseHas('person_documents', [
            'title' => 'Ugovor',
            'retention_proposed_at' => null,
        ]);
    }

    public function test_employee_cannot_confirm_retention(): void
    {
        Carbon::setTestNow('2026-09-19');
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);
        $person = Person::factory()->create(['organization_id' => $organization->id]);
        $type = DocumentType::query()->create([
            'organization_id' => $organization->id,
            'code' => 'ostalo_test',
            'name' => 'Bilješka',
            'retention_class' => RetentionClass::Other,
            'sort_order' => 90,
        ]);
        $document = PersonDocument::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'document_type_id' => $type->id,
            'title' => 'Stara',
            'issued_on' => '2018-01-10',
            'retain_until' => '2024-01-10',
            'retention_proposed_at' => now(),
        ]);

        $this->actingAs($employee)
            ->post(route('organization.settings.retention.confirm', [$organization->slug, $document]))
            ->assertForbidden();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Zadržavanje d.o.o.',
            'slug' => 'zadrzavanje-firma-'.uniqid(),
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
