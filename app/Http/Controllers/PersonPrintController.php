<?php

namespace App\Http\Controllers;

use App\Enums\ContractType;
use App\Enums\EmploymentInstrument;
use App\Models\Person;
use App\Services\ClockQrService;
use App\Services\OrganizationRbacService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PersonPrintController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly ClockQrService $clockQr,
    ) {}

    public function contract(string $slug, Person $person): View
    {
        $this->assertPerson($person);
        $this->authorizeSelfOrPeople($person);
        abort_unless($person->status->usesEmploymentContract(), 404);
        $person->load(['location', 'department', 'jobPosition', 'employmentContracts']);
        $contract = $person->currentContract();
        $kind = $contract?->kind ?? EmploymentInstrument::EmploymentContract;
        $type = $contract?->contract_type ?? $person->contract_type;
        $starts = $contract?->starts_at ?? $person->started_at;
        $ends = $contract?->ends_at;
        $hours = $contract?->weekly_hours ?? 40;
        $durationText = $this->durationText($type, $starts, $ends);
        $hoursText = $type === ContractType::PartTime
            ? 'Radnik radi nepuno radno vrijeme ('.$hours.' sati tjedno). Raspored utvrđuje aneks odnosno raspored rada.'
            : 'Puno radno vrijeme iznosi '.$hours.' sati tjedno, u pravilu 8 sati dnevno, ako internim aktom ili rasporedom nije drukčije određeno.';
        $trialText = $contract?->trial_ends_at
            ? 'Probni rad traje do '.$contract->trial_ends_at->format('d.m.Y.').'.'
            : null;

        return view('organization.people.contract', [
            'organization' => app('currentOrganization'),
            'person' => $person,
            'issuer' => Auth::user(),
            'issuedAt' => now(),
            'instrument' => $kind,
            'printTitle' => $kind->printTitle(),
            'durationText' => $durationText,
            'hoursText' => $hoursText,
            'trialText' => $trialText,
            'number' => $contract?->number ?: sprintf('UOR-%d/%d', $person->id, now()->year),
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

    public function articleTen(string $slug, Person $person): View
    {
        $this->assertPerson($person);
        $this->authorizeSelfOrPeople($person);
        abort_unless($person->status->usesArticleTen(), 404);
        $person->load(['location', 'department', 'jobPosition']);

        return view('organization.people.article-ten', [
            'organization' => app('currentOrganization'),
            'person' => $person,
            'exporter' => Auth::user(),
            'exportedAt' => now(),
        ]);
    }

    public function badge(string $slug, Person $person): View
    {
        $this->assertPerson($person);
        $this->authorizeSelfOrPeople($person);
        abort_unless($person->isClockEligible(), 404);
        $person->load(['location', 'department', 'jobPosition', 'organization']);

        return view('organization.people.badge', [
            'organization' => app('currentOrganization'),
            'person' => $person,
            'payload' => $this->clockQr->payload($person),
            'canRotate' => $this->rbac->can(app('currentOrganization')->id, (int) Auth::id(), 'people.access'),
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

    private function durationText(?ContractType $type, $starts, $ends): string
    {
        $startLabel = $starts ? $starts->format('d.m.Y.') : 'datumom potpisa';
        if ($type === ContractType::FixedTerm && $ends) {
            return 'Radni odnos počinje '.$startLabel.'. Ugovor se sklapa na određeno vrijeme, do '.$ends->format('d.m.Y.').'.';
        }
        if ($type === ContractType::FixedTerm) {
            return 'Radni odnos počinje '.$startLabel.'. Ugovor se sklapa na određeno vrijeme.';
        }

        return 'Radni odnos počinje '.$startLabel.'. Ugovor se sklapa na neodređeno vrijeme, osim ako je na kartici navedena druga vrsta.';
    }
}
