<?php

namespace App\Http\Controllers;

use App\Enums\QualificationKind;
use App\Models\Person;
use App\Models\Qualification;
use App\Services\OrganizationRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class QualificationController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function store(Request $request, string $slug, Person $person): RedirectResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $request->validate([
            'kind' => ['required', Rule::enum(QualificationKind::class)],
            'title' => ['required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'issued_on' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'required_for_job' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        Qualification::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'kind' => $data['kind'],
            'title' => $data['title'],
            'issuer' => $data['issuer'] ?? null,
            'issued_on' => $data['issued_on'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'required_for_job' => $request->boolean('required_for_job'),
            'note' => $data['note'] ?? null,
        ]);

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person])
            ->with('status', 'Kvalifikacija je spremljena.');
    }

    public function destroy(string $slug, Person $person, Qualification $qualification): RedirectResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($qualification->person_id === $person->id, 404);
        abort_unless($qualification->organization_id === $organization->id, 404);

        $qualification->delete();

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person])
            ->with('status', 'Kvalifikacija je uklonjena.');
    }

    private function assertPerson(Person $person): void
    {
        abort_unless($person->organization_id === app('currentOrganization')->id, 404);
    }
}
