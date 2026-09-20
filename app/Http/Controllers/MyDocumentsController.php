<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\PersonDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MyDocumentsController extends Controller
{
    public function index(): View
    {
        $organization = app('currentOrganization');
        $person = $this->ownPerson();
        abort_if($person === null, 403, 'Nemate povezanu karticu radnika.');

        return view('organization.my-documents.index', [
            'organization' => $organization,
            'person' => $person,
            'documents' => $person->documents()->with('documentType')->whereNull('disposed_at')->get(),
        ]);
    }

    public function download(string $slug, PersonDocument $document): StreamedResponse
    {
        $person = $this->ownPerson();
        abort_if($person === null, 403, 'Nemate povezanu karticu radnika.');
        abort_unless($document->person_id === $person->id, 404);
        abort_if($document->isDisposed(), 404);
        abort_unless($document->hasFile() && Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name ?: 'dokument');
    }

    private function ownPerson(): ?Person
    {
        $organization = app('currentOrganization');

        return Person::query()
            ->forOrganization($organization)
            ->where('user_id', (int) Auth::id())
            ->with(['documents.documentType'])
            ->first();
    }
}
