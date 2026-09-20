<?php

namespace App\Http\Controllers;

use App\Enums\FamilyKin;
use App\Models\Person;
use App\Models\PersonFamilyMember;
use App\Services\FamilyMemberService;
use App\Services\OrganizationRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FamilyMemberController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly FamilyMemberService $family,
    ) {}

    public function index(): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        return view('organization.family.index', [
            'organization' => $organization,
            'members' => PersonFamilyMember::query()
                ->forOrganization($organization)
                ->with('person')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'people' => Person::query()->forOrganization($organization)->orderBy('last_name')->orderBy('first_name')->get(),
            'kins' => FamilyKin::cases(),
        ]);
    }

    public function store(Request $request, string $slug, ?Person $person = null): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $request->validate([
            'person_id' => [
                Rule::requiredIf($person === null),
                Rule::exists('people', 'id')->where('organization_id', $organization->id),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'kin' => ['required', Rule::enum(FamilyKin::class)],
            'date_of_birth' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_dependent' => ['nullable', 'boolean'],
            'is_emergency_contact' => ['nullable', 'boolean'],
        ]);

        $target = $person ?? Person::query()->forOrganization($organization)->findOrFail($data['person_id']);
        abort_unless($target->organization_id === $organization->id, 404);

        $data['is_dependent'] = $request->boolean('is_dependent');
        $data['is_emergency_contact'] = $request->boolean('is_emergency_contact');
        $this->family->store($organization, $target, $data);

        return redirect()
            ->to($person
                ? route('organization.people.edit', [$organization->slug, $person, 'tab' => 'obitelj'])
                : route('organization.family.index', $organization->slug))
            ->with('status', 'Član obitelji je spremljen.');
    }

    public function destroy(string $slug, PersonFamilyMember $member): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($member->organization_id === $organization->id, 404);

        $person = $member->person;
        $this->family->destroy($member);

        $fromPerson = (string) request()->input('from') === 'person' && $person;

        return redirect()
            ->to($fromPerson
                ? route('organization.people.edit', [$organization->slug, $person, 'tab' => 'obitelj'])
                : route('organization.family.index', $organization->slug))
            ->with('status', 'Član obitelji je uklonjen.');
    }
}
