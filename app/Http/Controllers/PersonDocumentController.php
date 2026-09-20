<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\Person;
use App\Models\PersonDocument;
use App\Services\DocumentFillService;
use App\Services\DocumentInboxService;
use App\Services\FeatureService;
use App\Services\OrganizationRbacService;
use App\Support\OrganizationFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonDocumentController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly DocumentFillService $fill,
        private readonly FeatureService $features,
        private readonly DocumentInboxService $inbox,
    ) {}

    public function store(Request $request, string $slug, Person $person): RedirectResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $request->validate([
            'document_type_id' => [
                'required',
                Rule::exists('document_types', 'id')->where('organization_id', $organization->id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'issued_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'extensions:pdf,doc,docx,jpg,jpeg,png', 'max:4096'],
        ]);

        $type = DocumentType::query()
            ->where('organization_id', $organization->id)
            ->findOrFail($data['document_type_id']);

        $filePath = null;
        $original = null;
        $mime = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('person-documents/'.$organization->id.'/'.$person->id, 'local');
            $original = $file->getClientOriginalName();
            $mime = $file->getClientMimeType();
        }

        $created = PersonDocument::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'document_type_id' => $type->id,
            'title' => $data['title'] ?: $type->name,
            'issued_on' => $data['issued_on'] ?? null,
            'expires_on' => $data['expires_on'] ?? null,
            'note' => $data['note'] ?? null,
            'file_path' => $filePath,
            'original_name' => $original,
            'mime' => $mime,
        ]);
        $this->inbox->notifyNew($organization, $person->loadMissing('user'), $created);

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person, 'tab' => 'dokumenti'])
            ->with('status', 'Dokument je spremljen.');
    }

    public function download(string $slug, Person $person, PersonDocument $document): StreamedResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->authorizeView($person);
        abort_unless($document->person_id === $person->id, 404);
        abort_unless($document->organization_id === $organization->id, 404);
        abort_if($document->isDisposed(), 404);
        abort_unless($document->hasFile() && Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name ?: 'dokument');
    }

    public function fill(string $slug, Person $person, DocumentTemplate $template)
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $this->features->assertEnabled($organization, OrganizationFeatures::DOCUMENT_TEMPLATES);
        abort_unless($template->organization_id === $organization->id, 404);
        abort_unless(Storage::disk('local')->exists($template->file_path), 404);

        $contents = $this->fill->filledContents($template, $person);
        $name = $this->fill->downloadName($template, $person);

        return response()->streamDownload(function () use ($contents) {
            echo $contents;
        }, $name, [
            'Content-Type' => $template->mime ?: 'application/octet-stream',
        ]);
    }

    public function fillStore(string $slug, Person $person, DocumentTemplate $template): RedirectResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $this->features->assertEnabled($organization, OrganizationFeatures::DOCUMENT_TEMPLATES);
        abort_unless($template->organization_id === $organization->id, 404);
        abort_unless(Storage::disk('local')->exists($template->file_path), 404);

        $document = $this->fill->storeFilled($template, $person);

        $this->inbox->notifyNew($organization, $person->loadMissing('user'), $document);

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person, 'tab' => 'dokumenti'])
            ->with('status', 'Akt je spremljen u dosje: '.$document->title);
    }

    public function destroy(string $slug, Person $person, PersonDocument $document): RedirectResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($document->person_id === $person->id, 404);
        abort_unless($document->organization_id === $organization->id, 404);

        $document->deleteFile();
        $document->delete();

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person, 'tab' => 'dokumenti'])
            ->with('status', 'Dokument je uklonjen.');
    }

    private function assertPerson(Person $person): void
    {
        abort_unless($person->organization_id === app('currentOrganization')->id, 404);
    }

    private function authorizeView(Person $person): void
    {
        $organization = app('currentOrganization');
        $userId = (int) Auth::id();
        if ($this->rbac->can($organization->id, $userId, 'people.access')) {
            return;
        }

        abort_unless((int) $person->user_id === $userId, 403, 'Nemate ovlasti za ovu radnju.');
    }
}
