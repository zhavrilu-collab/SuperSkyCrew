<?php

namespace App\Http\Controllers;

use App\Enums\ContractType;
use App\Enums\EmploymentInstrument;
use App\Enums\FamilyRight;
use App\Enums\PersonStatus;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Location;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Rules\ValidOib;
use App\Services\DepartmentScopeService;
use App\Services\EmploymentContractService;
use App\Services\ExpiryWarningService;
use App\Services\OrganizationRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly ExpiryWarningService $expiries,
        private readonly DepartmentScopeService $scope,
        private readonly EmploymentContractService $contracts,
    ) {}

    public function index(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $statusFilter = $request->input('status');
        $people = Person::query()
            ->forOrganization($organization)
            ->with(['user', 'location', 'department', 'jobPosition', 'costCenter'])
            ->when(
                is_string($statusFilter) && $statusFilter !== '',
                fn ($query) => $query->where('status', $statusFilter),
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('organization.people.index', [
            'organization' => $organization,
            'people' => $people,
            'statusFilter' => $statusFilter,
            'statuses' => PersonStatus::cases(),
            'expiryCounts' => $this->expiries->countsByPerson($this->expiries->due($organization)),
        ]);
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $this->validated($request);
        $data['organization_id'] = $organization->id;

        Person::query()->create($data);

        return redirect()
            ->route('organization.people.index', $organization->slug)
            ->with('status', 'Osoba je spremljena.');
    }

    public function edit(string $slug, Person $person): View
    {
        $this->assertPerson($person);

        return $this->form($person);
    }

    public function update(Request $request, string $slug, Person $person): RedirectResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $person->update($this->validated($request, $person));

        return redirect()
            ->route('organization.people.index', $organization->slug)
            ->with('status', 'Kartica je ažurirana.');
    }

    public function hire(Request $request, string $slug, Person $person): RedirectResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        if ($person->status !== PersonStatus::Candidate) {
            return redirect()
                ->route('organization.people.edit', [$organization->slug, $person])
                ->withErrors(['status' => 'U kadar se može prenijeti samo kandidat.']);
        }

        $person->update([
            'status' => PersonStatus::Employee,
            'started_at' => $person->started_at?->toDateString() ?? now()->toDateString(),
        ]);
        $this->contracts->seedIfMissing($person->fresh(), $request->user());

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person])
            ->with('status', 'Kandidat je prenesen u kadar.');
    }

    public function review(string $slug, Person $person): View
    {
        $this->assertPerson($person);
        $this->authorizeReview($person);

        $person->load(['location', 'department', 'jobPosition', 'costCenter', 'qualifications', 'employmentContracts']);

        return view('organization.people.review', [
            'organization' => app('currentOrganization'),
            'person' => $person,
            'exporter' => Auth::user(),
            'exportedAt' => now(),
        ]);
    }

    private function form(?Person $person): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $linkedUserIds = Person::query()
            ->forOrganization($organization)
            ->whereNotNull('user_id')
            ->when($person, fn ($q) => $q->where('id', '!=', $person->id))
            ->pluck('user_id');

        $users = OrganizationUser::query()
            ->with('user')
            ->where('organization_id', $organization->id)
            ->whereNotIn('user_id', $linkedUserIds)
            ->get()
            ->map(fn (OrganizationUser $membership) => $membership->user)
            ->filter();

        if ($person?->user) {
            $users->prepend($person->user);
        }

        $managers = OrganizationUser::query()
            ->with('user')
            ->where('organization_id', $organization->id)
            ->whereIn('role', ['owner', 'hr', 'manager'])
            ->get()
            ->map(fn (OrganizationUser $membership) => $membership->user)
            ->filter()
            ->unique('id')
            ->values();

        $on = now()->timezone(config('app.timezone'));
        $departments = $this->scope->departmentsOn($organization, $on);
        if ($person?->department && ! $departments->contains('id', $person->department_id)) {
            $departments->push($person->department);
        }
        $positions = $this->scope->positionsOn($organization, $on);
        if ($person?->jobPosition && ! $positions->contains('id', $person->job_position_id)) {
            $positions->push($person->jobPosition);
        }
        $costCenters = $this->scope->costCentersOn($organization, $on);
        if ($person?->costCenter && ! $costCenters->contains('id', $person->cost_center_id)) {
            $costCenters->push($person->costCenter);
        }

        if ($person) {
            $person->load(['qualifications', 'employmentContracts']);
        }

        return view('organization.people.form', [
            'organization' => $organization,
            'person' => $person,
            'locations' => Location::query()->forOrganization($organization)->where('is_active', true)->orderBy('name')->get(),
            'departments' => $departments,
            'positions' => $positions,
            'costCenters' => $costCenters,
            'users' => $users->unique('id')->values(),
            'managers' => $managers,
            'statuses' => PersonStatus::cases(),
            'contracts' => ContractType::cases(),
            'qualificationKinds' => \App\Enums\QualificationKind::cases(),
            'instrumentKinds' => EmploymentInstrument::cases(),
            'familyRights' => FamilyRight::cases(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Person $person = null): array
    {
        $organization = app('currentOrganization');

        $iban = strtoupper(preg_replace('/\s+/', '', (string) $request->input('iban', '')) ?? '');
        $request->merge(['iban' => $iban === '' ? null : $iban]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'oib' => ['nullable', 'digits:11', new ValidOib],
            'gender' => ['nullable', Rule::in(['m', 'z', 'x'])],
            'date_of_birth' => ['nullable', 'date'],
            'citizenship' => ['nullable', 'string', 'max:80'],
            'residence' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'contract_type' => ['nullable', Rule::enum(ContractType::class)],
            'status' => ['required', Rule::enum(PersonStatus::class)],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date'],
            'ended_reason' => ['nullable', 'string', 'max:255'],
            'insurance_filed_at' => ['nullable', 'date'],
            'work_permit_expires_at' => ['nullable', 'date'],
            'medical_expires_at' => ['nullable', 'date'],
            'certificate_expires_at' => ['nullable', 'date'],
            'location_id' => [
                'nullable',
                Rule::exists('locations', 'id')->where('organization_id', $organization->id),
            ],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('organization_id', $organization->id),
            ],
            'job_position_id' => [
                'nullable',
                Rule::exists('job_positions', 'id')->where('organization_id', $organization->id),
            ],
            'cost_center_id' => [
                'nullable',
                Rule::exists('cost_centers', 'id')->where('organization_id', $organization->id),
            ],
            'user_id' => [
                'nullable',
                Rule::exists('organization_users', 'user_id')->where('organization_id', $organization->id),
                Rule::unique('people', 'user_id')
                    ->where('organization_id', $organization->id)
                    ->ignore($person?->id),
            ],
            'manager_user_id' => [
                'nullable',
                Rule::exists('organization_users', 'user_id')->where('organization_id', $organization->id),
            ],
            'annual_leave_days' => ['nullable', 'integer', 'min:0', 'max:50'],
            'iban' => ['nullable', 'string', 'max:34', 'regex:/^[A-Za-z]{2}[0-9]{2}[A-Za-z0-9]{11,30}$/'],
            'pay_coefficient' => ['nullable', 'numeric', 'min:0', 'max:99.9999'],
            'allowance_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'prior_service_months' => ['nullable', 'integer', 'min:0', 'max:720'],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'dependents_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'tax_relief_note' => ['nullable', 'string', 'max:255'],
            'family_right' => ['nullable', Rule::enum(FamilyRight::class)],
            'znr_exam_required' => ['nullable', 'boolean'],
            'clock_pin' => [
                'nullable',
                'digits_between:4,6',
                Rule::unique('people', 'clock_pin')
                    ->where('organization_id', $organization->id)
                    ->ignore($person?->id),
            ],
        ]);

        foreach (['location_id', 'department_id', 'job_position_id', 'cost_center_id', 'user_id', 'manager_user_id', 'oib', 'contract_type', 'clock_pin', 'iban', 'family_right', 'tax_relief_note', 'pay_coefficient', 'allowance_percent', 'prior_service_months'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        if (! empty($data['iban'])) {
            $data['iban'] = strtoupper(preg_replace('/\s+/', '', (string) $data['iban']));
        }

        $data['znr_exam_required'] = $request->boolean('znr_exam_required');
        $data['children_count'] = (int) ($data['children_count'] ?? 0);
        $data['dependents_count'] = (int) ($data['dependents_count'] ?? 0);

        if (! empty($data['job_position_id'])) {
            $position = JobPosition::query()
                ->where('organization_id', $organization->id)
                ->find($data['job_position_id']);
            if ($position) {
                $data['job_title'] = $position->name;
                if (! isset($data['annual_leave_days']) || $data['annual_leave_days'] === null) {
                    $data['annual_leave_days'] = $position->annual_leave_days;
                }
            }
        }

        if (! empty($data['department_id']) && empty($data['manager_user_id'])) {
            $department = Department::query()
                ->where('organization_id', $organization->id)
                ->find($data['department_id']);
            if ($department?->manager_user_id) {
                $data['manager_user_id'] = $department->manager_user_id;
            }
        }

        return $data;
    }

    private function assertPerson(Person $person): void
    {
        $organization = app('currentOrganization');
        abort_unless($person->organization_id === $organization->id, 404);
    }

    private function authorizeReview(Person $person): void
    {
        $organization = app('currentOrganization');
        $userId = (int) Auth::id();

        if ($this->rbac->can($organization->id, $userId, 'people.access')) {
            return;
        }

        abort_unless((int) $person->user_id === $userId, 403, 'Nemate ovlasti za ovu radnju.');
    }
}
