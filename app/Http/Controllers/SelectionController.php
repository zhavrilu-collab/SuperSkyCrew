<?php

namespace App\Http\Controllers;

use App\Enums\InterviewOutcome;
use App\Models\InterviewNote;
use App\Models\Person;
use App\Services\OrganizationRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SelectionController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function storeCv(Request $request, string $slug, Person $person): RedirectResponse
    {
        $this->assertHr($person);
        $organization = app('currentOrganization');

        $request->validate([
            'cv' => ['required', 'file', 'extensions:pdf,doc,docx', 'max:4096'],
        ]);

        $file = $request->file('cv');
        if ($person->cv_path) {
            Storage::disk('local')->delete($person->cv_path);
        }
        $path = $file->store('person-cv/'.$organization->id.'/'.$person->id, 'local');
        $person->forceFill([
            'cv_path' => $path,
            'cv_original_name' => $file->getClientOriginalName(),
            'cv_uploaded_at' => now(),
        ])->save();

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person, 'tab' => 'odabir'])
            ->with('status', 'CV je spremljen.');
    }

    public function downloadCv(string $slug, Person $person): StreamedResponse
    {
        $this->assertHr($person);
        abort_unless($person->hasCv() && Storage::disk('local')->exists($person->cv_path), 404);

        return Storage::disk('local')->download($person->cv_path, $person->cv_original_name ?: 'cv');
    }

    public function destroyCv(string $slug, Person $person): RedirectResponse
    {
        $this->assertHr($person);
        $organization = app('currentOrganization');
        $person->deleteCv();

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person, 'tab' => 'odabir'])
            ->with('status', 'CV je uklonjen.');
    }

    public function storeNote(Request $request, string $slug, Person $person): RedirectResponse
    {
        $this->assertHr($person);
        $organization = app('currentOrganization');

        $data = $request->validate([
            'occurred_on' => ['required', 'date'],
            'interviewer_user_id' => [
                'nullable',
                Rule::exists('organization_users', 'user_id')->where('organization_id', $organization->id),
            ],
            'interviewer_name' => ['nullable', 'string', 'max:120'],
            'outcome' => ['required', Rule::enum(InterviewOutcome::class)],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        if (($data['interviewer_user_id'] ?? null) === '') {
            $data['interviewer_user_id'] = null;
        }

        InterviewNote::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'occurred_on' => $data['occurred_on'],
            'interviewer_user_id' => $data['interviewer_user_id'] ?? null,
            'interviewer_name' => $data['interviewer_name'] ?? null,
            'outcome' => $data['outcome'],
            'body' => $data['body'],
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person, 'tab' => 'odabir'])
            ->with('status', 'Bilješka razgovora je spremljena.');
    }

    public function destroyNote(string $slug, Person $person, InterviewNote $note): RedirectResponse
    {
        $this->assertHr($person);
        $organization = app('currentOrganization');
        abort_unless($note->person_id === $person->id, 404);
        abort_unless($note->organization_id === $organization->id, 404);
        $note->delete();

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person, 'tab' => 'odabir'])
            ->with('status', 'Bilješka je uklonjena.');
    }

    private function assertHr(Person $person): void
    {
        $organization = app('currentOrganization');
        abort_unless($person->organization_id === $organization->id, 404);
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
    }
}
