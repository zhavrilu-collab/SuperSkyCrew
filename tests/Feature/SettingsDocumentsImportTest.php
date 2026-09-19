<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use App\Services\DocumentFillService;
use App\Services\HrSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsDocumentsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_attach_and_download_person_document_file(): void
    {
        Storage::fake('local');
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        app(HrSetupService::class)->provision($organization);
        $type = DocumentType::query()->where('organization_id', $organization->id)->where('code', 'uor')->firstOrFail();
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);
        $file = UploadedFile::fake()->create('ugovor.pdf', 30, 'application/pdf');

        $this->actingAs($owner)
            ->post(route('organization.documents.store', [$organization->slug, $person]), [
                'document_type_id' => $type->id,
                'title' => 'UOR-1',
                'file' => $file,
            ])
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'dokumenti']));

        $document = $person->documents()->first();
        $this->assertNotNull($document?->file_path);
        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($owner)
            ->get(route('organization.documents.download', [$organization->slug, $person, $document]))
            ->assertOk();
    }

    public function test_employee_cannot_upload_person_document(): void
    {
        Storage::fake('local');
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        $employee = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'role' => OrganizationRole::Employee,
        ]);
        app(HrSetupService::class)->provision($organization);
        $type = DocumentType::query()->where('organization_id', $organization->id)->where('code', 'uor')->firstOrFail();
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'status' => PersonStatus::Employee,
        ]);

        $this->actingAs($employee)
            ->post(route('organization.documents.store', [$organization->slug, $person]), [
                'document_type_id' => $type->id,
                'title' => 'Tajno',
                'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_owner_uploads_docx_template_and_fills_person_fields(): void
    {
        Storage::fake('local');
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        app(HrSetupService::class)->provision($organization);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Kovač',
            'oib' => '12345678903',
            'status' => PersonStatus::Employee,
        ]);
        $path = tempnam(sys_get_temp_dir(), 'docx').'.docx';
        file_put_contents($path, DocumentFillService::sampleDocx());
        $upload = new UploadedFile($path, 'uor.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $this->actingAs($owner)
            ->post(route('organization.settings.templates.store', $organization->slug), [
                'name' => 'UOR predložak',
                'file' => $upload,
            ])
            ->assertRedirect();

        $template = DocumentTemplate::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'UOR predložak')
            ->firstOrFail();
        $this->assertTrue($template->isDocx());

        $download = $this->actingAs($owner)
            ->get(route('organization.documents.fill', [$organization->slug, $person, $template]))
            ->assertOk()
            ->streamedContent();

        $out = tempnam(sys_get_temp_dir(), 'out').'.docx';
        file_put_contents($out, $download);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($out));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertIsString($xml);
        $this->assertStringContainsString('Iva', $xml);
        $this->assertStringContainsString('Kovač', $xml);
        $this->assertStringContainsString('12345678903', $xml);
        @unlink($path);
        @unlink($out);
    }

    public function test_people_csv_import_creates_updates_and_skips_invalid_oib(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Stara',
            'last_name' => 'Osoba',
            'oib' => '12345678903',
            'status' => PersonStatus::Employee,
        ]);

        $csv = implode("\n", [
            'ime;prezime;oib;status;radno_mjesto',
            'Nova;Osoba;12345678903;employee;Referentica',
            'Petra;Dolazak;;kandidat;Pripravnica',
            'Krivi;Oib;12345678901;employee;X',
        ]);
        $file = UploadedFile::fake()->createWithContent('kadar.csv', $csv);

        $this->actingAs($owner)
            ->post(route('organization.settings.import', $organization->slug), [
                'file' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('people', [
            'organization_id' => $organization->id,
            'oib' => '12345678903',
            'first_name' => 'Nova',
            'job_title' => 'Referentica',
        ]);
        $this->assertDatabaseHas('people', [
            'organization_id' => $organization->id,
            'first_name' => 'Petra',
            'last_name' => 'Dolazak',
            'status' => PersonStatus::Candidate->value,
        ]);
        $this->assertDatabaseMissing('people', [
            'organization_id' => $organization->id,
            'last_name' => 'Oib',
        ]);
        $this->assertNotEmpty(session('import_skipped'));
    }

    public function test_employee_cannot_import_people(): void
    {
        [$employee, $organization] = $this->seedMember(OrganizationRole::Employee);
        $file = UploadedFile::fake()->createWithContent('kadar.csv', "ime;prezime\nA;B\n");

        $this->actingAs($employee)
            ->post(route('organization.settings.import', $organization->slug), [
                'file' => $file,
            ])
            ->assertForbidden();
    }

    public function test_settings_show_templates_and_import(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'kadar',
                'section' => 'predlosci',
            ]))
            ->assertOk()
            ->assertSee('Predlošci')
            ->assertSee('{{ime}}', false)
            ->assertSee('{{go_broj}}', false);

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'podaci',
                'section' => 'uvoz',
            ]))
            ->assertOk()
            ->assertSee('Uvezi kadar')
            ->assertDontSee('uskoro');
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Datoteke d.o.o.',
            'slug' => 'datoteke-'.uniqid(),
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
