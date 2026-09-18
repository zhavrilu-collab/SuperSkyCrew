<?php

namespace App\Http\Controllers;

use App\Enums\DocumentKind;
use App\Models\DocumentHandover;
use App\Models\Person;
use App\Services\OrganizationRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentHandoverController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function index(): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        return view('organization.handovers.index', [
            'organization' => $organization,
            'handovers' => DocumentHandover::query()
                ->forOrganization($organization)
                ->with(['person', 'recordedBy'])
                ->orderByDesc('handed_on')
                ->orderByDesc('id')
                ->get(),
            'people' => Person::query()
                ->forOrganization($organization)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'kinds' => DocumentKind::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $request->validate([
            'handed_on' => ['required', 'date'],
            'recipient' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:255'],
            'document_kind' => ['required', Rule::enum(DocumentKind::class)],
            'person_id' => [
                'nullable',
                Rule::exists('people', 'id')->where('organization_id', $organization->id),
            ],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if (($data['person_id'] ?? null) === '') {
            $data['person_id'] = null;
        }

        DocumentHandover::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $data['person_id'] ?? null,
            'recorded_by_user_id' => Auth::id(),
            'handed_on' => $data['handed_on'],
            'recipient' => $data['recipient'],
            'purpose' => $data['purpose'],
            'document_kind' => $data['document_kind'],
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', 'Predaja je zabilježena.');
    }
}
