<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Services\OrganizationRbacService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PersonPrintController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function contract(string $slug, Person $person): View
    {
        $this->assertPerson($person);
        $this->authorizeSelfOrPeople($person);
        $person->load(['location', 'department', 'jobPosition']);

        return view('organization.people.contract', [
            'organization' => app('currentOrganization'),
            'person' => $person,
            'issuer' => Auth::user(),
            'issuedAt' => now(),
            'number' => sprintf('UOR-%d/%d', $person->id, now()->year),
        ]);
    }

    public function referral(string $slug, Person $person): View
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $person->load(['location', 'department', 'jobPosition', 'qualifications']);

        return view('organization.people.referral', [
            'organization' => $organization,
            'person' => $person,
            'issuer' => Auth::user(),
            'issuedAt' => now(),
            'number' => sprintf('UPUT-%d/%s', $person->id, now()->format('Ymd')),
        ]);
    }

    private function assertPerson(Person $person): void
    {
        abort_unless($person->organization_id === app('currentOrganization')->id, 404);
    }

    private function authorizeSelfOrPeople(Person $person): void
    {
        $organization = app('currentOrganization');
        $userId = (int) Auth::id();

        if ($this->rbac->can($organization->id, $userId, 'people.access')) {
            return;
        }

        abort_unless((int) $person->user_id === $userId, 403, 'Nemate ovlasti za ovu radnju.');
    }
}
