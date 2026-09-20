<?php

namespace App\Http\Controllers;

use App\Enums\OrgSeatStatus;
use App\Models\JobPosition;
use App\Models\OrgPosition;
use App\Models\Person;
use App\Services\OrganizationRbacService;
use App\Services\OrgPositionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrgPositionController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly OrgPositionService $seats,
    ) {}

    public function index(): View
    {
        return $this->listing('Ustroj tvrtke');
    }

    public function plan(): View
    {
        return $this->listing('Sistematizacija');
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $data = $this->validated($request);
        $job = JobPosition::query()->forOrganization($organization)->findOrFail($data['job_position_id']);
        OrgPosition::query()->create([
            'organization_id' => $organization->id,
            'job_position_id' => $job->id,
            'department_id' => $data['department_id'] ?? $job->department_id,
            'seat_no' => $this->seats->nextSeatNo($organization->id, $job->id),
            'status' => $data['status'] ?? OrgSeatStatus::Open->value,
            'valid_from' => $data['valid_from'] ?? now()->toDateString(),
            'valid_to' => $data['valid_to'] ?? null,
        ]);

        return back()->with('status', 'Radna pozicija je otvorena.');
    }

    public function update(Request $request, string $slug, OrgPosition $position): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($position->organization_id === $organization->id, 404);
        $data = $this->validated($request, $position);
        $position->update([
            'status' => $data['status'] ?? $position->status,
            'department_id' => $data['department_id'] ?? $position->department_id,
            'valid_from' => $data['valid_from'] ?? $position->valid_from,
            'valid_to' => array_key_exists('valid_to', $data) ? $data['valid_to'] : $position->valid_to,
        ]);

        $personId = $request->input('person_id');
        if ($personId) {
            $person = Person::query()->forOrganization($organization)->findOrFail($personId);
            $this->seats->assign($position->fresh(['jobPosition']), $person);
        } elseif ($request->boolean('release')) {
            $this->seats->release($position);
        }

        return back()->with('status', 'Radna pozicija je ažurirana.');
    }

    public function destroy(string $slug, OrgPosition $position): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($position->organization_id === $organization->id, 404);
        if ($position->person_id) {
            return back()->withErrors(['position' => 'Pozicija je popunjena. Prvo skinite osobu sa stolice.']);
        }
        $position->delete();

        return back()->with('status', 'Radna pozicija je uklonjena.');
    }

    private function listing(string $nav): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        return view('organization.positions.index', [
            'organization' => $organization,
            'nav' => $nav,
            'seats' => OrgPosition::query()
                ->forOrganization($organization)
                ->with(['jobPosition', 'department', 'person'])
                ->orderBy('job_position_id')
                ->orderBy('seat_no')
                ->get(),
            'jobs' => JobPosition::query()->forOrganization($organization)->orderBy('name')->get(),
            'people' => Person::query()->forOrganization($organization)->orderBy('last_name')->get(),
            'statuses' => OrgSeatStatus::cases(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?OrgPosition $position = null): array
    {
        $organization = app('currentOrganization');
        $data = $request->validate([
            'job_position_id' => [
                $position ? 'nullable' : 'required',
                Rule::exists('job_positions', 'id')->where('organization_id', $organization->id),
            ],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('organization_id', $organization->id),
            ],
            'status' => ['nullable', Rule::enum(OrgSeatStatus::class)],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'person_id' => [
                'nullable',
                Rule::exists('people', 'id')->where('organization_id', $organization->id),
            ],
        ]);

        foreach (['department_id', 'valid_from', 'valid_to', 'person_id'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        return $data;
    }
}
