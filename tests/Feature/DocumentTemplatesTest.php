<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Models\DocumentTemplate;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\PersonDocument;
use App\Models\User;
use App\Services\DocumentFillService;
use App\Services\HrSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_provision_seeds_system_docx_templates(): void
    {
        Storage::fake('local');
        [$owner, $organization] = $this->seedMember();
        app(HrSetupService::class)->provision($organization);

        $this->assertSame(3, DocumentTemplate::query()->where('organization_id', $organization->id)->where('is_system', true)->count());
        Storage::disk('local')->assertExists('document-templates/'.$organization->id.'/uor.docx');

        $this->actingAs($owner)
            ->delete(route('organization.settings.templates.destroy', [
                $organization->slug,
                DocumentTemplate::query()->where('kind', 'uor')->firstOrFail(),
            ]))
            ->assertUnprocessable();
    }

    public function test_fill_replaces_split_word_runs_and_saves_to_dossier(): void
    {
        Storage::fake('local');
        [$owner, $organization] = $this->seedMember();
        app(HrSetupService::class)->provision($organization);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Iva',
            'last_name' => 'Kovač',
            'oib' => '12345678903',
            'status' => PersonStatus::Employee,
        ]);

        $fill = app(DocumentFillService::class);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>{{</w:t></w:r><w:r><w:t>ime}}</w:t></w:r><w:r><w:t> {{prezime}} {{organizacija_oib}}</w:t></w:r></w:p></w:body></w:document>';
        $path = tempnam(sys_get_temp_dir(), 'split').'.docx';
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        $organization->update(['oib' => '12345678903']);
        $template = DocumentTemplate::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Split token',
            'file_path' => 'document-templates/'.$organization->id.'/split.docx',
            'original_name' => 'split.docx',
            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'is_system' => false,
        ]);
        Storage::disk('local')->put($template->file_path, file_get_contents($path) ?: '');
        @unlink($path);

        $download = $this->actingAs($owner)
            ->get(route('organization.documents.fill', [$organization->slug, $person, $template]))
            ->assertOk()
            ->streamedContent();

        $out = tempnam(sys_get_temp_dir(), 'out').'.docx';
        file_put_contents($out, $download);
        $read = new \ZipArchive;
        $this->assertTrue($read->open($out));
        $filled = $read->getFromName('word/document.xml');
        $read->close();
        @unlink($out);
        $this->assertIsString($filled);
        $this->assertStringContainsString('Iva', $filled);
        $this->assertStringContainsString('Kovač', $filled);
        $this->assertStringContainsString('12345678903', $filled);

        $this->actingAs($owner)
            ->post(route('organization.documents.fill-store', [$organization->slug, $person, $template]))
            ->assertRedirect(route('organization.people.edit', [$organization->slug, $person, 'tab' => 'dokumenti']));

        $document = PersonDocument::query()->where('person_id', $person->id)->where('title', 'Split token')->first();
        $this->assertNotNull($document);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_system_uor_template_fills_person_name(): void
    {
        Storage::fake('local');
        [$owner, $organization] = $this->seedMember();
        $organization->update(['name' => 'Akti d.o.o.', 'oib' => '12345678903']);
        app(HrSetupService::class)->provision($organization);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Marko',
            'last_name' => 'Horvat',
            'status' => PersonStatus::Employee,
        ]);
        $template = DocumentTemplate::query()->where('organization_id', $organization->id)->where('kind', 'uor')->firstOrFail();

        $download = $this->actingAs($owner)
            ->get(route('organization.documents.fill', [$organization->slug, $person, $template]))
            ->assertOk()
            ->streamedContent();

        $out = tempnam(sys_get_temp_dir(), 'uor').'.docx';
        file_put_contents($out, $download);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($out));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($out);
        $this->assertIsString($xml);
        $this->assertStringContainsString('Marko Horvat', $xml);
        $this->assertStringContainsString('Akti d.o.o.', $xml);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Akti d.o.o.',
            'slug' => 'akti-'.uniqid(),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$user, $organization];
    }
}
