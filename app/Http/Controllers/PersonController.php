<?php

namespace App\Http\Controllers;

use App\Enums\ContractType;
use App\Enums\EmploymentInstrument;
use App\Enums\FamilyRight;
use App\Enums\OtherFoKind;
use App\Enums\PersonStatus;
use App\Enums\AuditAction;
use App\Models\Department;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\JobPosition;
use App\Models\Location;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Rules\ValidOib;
use App\Services\DepartmentScopeService;
use App\Services\EmploymentContractService;
use App\Services\ExpiryWarningService;
use App\Services\LeaveService;
use App\Services\ClockQrService;
use App\Services\OrganizationRbacService;
use App\Services\AuditService;
use App\Services\FeatureService;
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
        private readonly AuditService $audit,
        private readonly LeaveService $leave,
        private readonly ClockQrService $clockQr,
        private readonly FeatureService $features,
    ) {}

    public function index(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $statusFilter = $request->input('status');
        $people = Person::query()
            ->forOrganization($organization)
            ->with(['user', 'location', 'department', 'jobPosition', 'costCenter'])
            ->withCount('interviewNotes')
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
        $this->features->assertSeat($organization, $data['status'] ?? null);
        $data['organization_id'] = $organization->id;

        $person = Person::query()->create($data);
        $this->leave->applyToPerson($person);
        $tab = $person->status === PersonStatus::Candidate ? 'odabir' : 'pregled';

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person, 'tab' => $tab])
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

        $data = $this->validated($request, $person);
        $this->features->assertSeat($organization, $data['status'] ?? null, $person);
        if ($request->boolean('clock_device_reset')) {
            $data['clock_device_id'] = null;
        }
        $person->update($data);
        $this->leave->applyToPerson($person->fresh(['organization', 'jobPosition']));

        return redirect()
            ->route('organization.people.edit', [
                $organization->slug,
                $person,
                'tab' => $this->profileTab($request->input('return_tab')),
            ])
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

        $this->features->assertSeat($organization, PersonStatus::Employee->value, $person);

        $person->update([
            'status' => PersonStatus::Employee,
            'started_at' => $person->started_at?->toDateString() ?? now()->toDateString(),
        ]);
        $this->contracts->seedIfMissing($person->fresh(), $request->user());
        $this->leave->applyToPerson($person->fresh(['organization', 'jobPosition']));
        $this->audit->record(
            $organization,
            AuditAction::PersonHire,
            $request->user(),
            'Kandidat prenesen u kadar: '.$person->fullName(),
            $person->fresh(),
            Person::class,
            $person->id,
        );

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person])
            ->with('status', 'Kandidat je prenesen u kadar.');
    }

    public function rotateQr(Request $request, string $slug, Person $person): RedirectResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $this->clockQr->rotate($person);
        $this->audit->record(
            $organization,
            AuditAction::ClockQrRotate,
            $request->user(),
            'Obnovljen QR kiosk kod: '.$person->fullName(),
            $person,
            Person::class,
            $person->id,
        );

        return redirect()
            ->route('organization.people.badge', [$organization->slug, $person])
            ->with('status', 'Novi QR kod je spreman. Stari više ne vrijedi.');
    }

    public function rotateClockToken(Request $request, string $slug, Person $person): RedirectResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $person->forceFill(['clock_api_token' => bin2hex(random_bytes(16))])->save();
        $this->audit->record(
            $organization,
            AuditAction::ClockTokenRotate,
            $request->user(),
            'Obnovljen Clock API token: '.$person->fullName(),
            $person,
            Person::class,
            $person->id,
        );

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person, 'tab' => 'angazman'])
            ->with('status', 'Novi Clock API token je spreman. Stari više ne vrijedi.');
    }

    public function review(string $slug, Person $person): View
    {
        $this->assertPerson($person);
        $this->authorizeReview($person);

        $person->load(['location', 'department', 'jobPosition', 'costCenter', 'qualifications', 'employmentContracts']);

        $view = match (true) {
            $person->status->usesArticleTen() => 'organization.people.article-ten',
            $person->status === PersonStatus::Contractor => 'organization.people.contractor',
            $person->status === PersonStatus::Volunteer => 'organization.people.volunteer',
            default => 'organization.people.review',
        };

        return view($view, [
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
        app(\App\Services\OrganizationStructureService::class)->ensure($organization);
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
        $legalEntities = $this->scope->legalEntitiesOn($organization, $on);
        if ($person?->legalEntity && ! $legalEntities->contains('id', $person->legal_entity_id)) {
            $legalEntities->push($person->legalEntity);
        }
        $workCenters = $this->scope->workCentersOn($organization, $on);
        if ($person?->workCenter && ! $workCenters->contains('id', $person->work_center_id)) {
            $workCenters->push($person->workCenter);
        }

        if ($person) {
            $person->load(['qualifications', 'employmentContracts', 'documents.documentType', 'user', 'manager', 'department', 'jobPosition', 'location', 'costCenter', 'legalEntity', 'workCenter', 'engagements.department', 'engagements.jobPosition', 'engagements.location', 'engagements.costCenter', 'engagements.changedByUser', 'interviewNotes.interviewer', 'interviewNotes.author']);
            app(\App\Services\HrSetupService::class)->provision($organization);
        }

        return view('organization.people.form', [
            'organization' => $organization,
            'person' => $person,
            'profileTab' => $this->profileTab(request()->query('tab')),
            'expiryCount' => $person ? (int) ($this->expiries->countsByPerson($this->expiries->due($organization))[$person->id] ?? 0) : 0,
            'locations' => Location::query()->forOrganization($organization)->where('is_active', true)->orderBy('name')->get(),
            'departments' => $departments,
            'positions' => $positions,
            'costCenters' => $costCenters,
            'legalEntities' => $legalEntities,
            'workCenters' => $workCenters,
            'users' => $users->unique('id')->values(),
            'managers' => $managers,
            'statuses' => $this->selectableStatuses($organization, $person),
            'contracts' => ContractType::cases(),
            'qualificationKinds' => \App\Enums\QualificationKind::cases(),
            'instrumentKinds' => EmploymentInstrument::cases(),
            'familyRights' => FamilyRight::cases(),
            'otherFoKinds' => OtherFoKind::cases(),
            'documentTypes' => DocumentType::query()->forOrganization($organization)->orderBy('sort_order')->orderBy('name')->get(),
            'documentTemplates' => DocumentTemplate::query()->forOrganization($organization)->orderBy('name')->get(),
            'interviewOutcomes' => \App\Enums\InterviewOutcome::cases(),
            'leaveSnapshot' => $person && $person->status->usesArticleThree()
                ? $this->leave->snapshot($person)
                : null,
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
            'status' => [
                'required',
                Rule::enum(PersonStatus::class),
                function (string $attribute, mixed $value, \Closure $fail) use ($organization, $person): void {
                    if ($value === PersonStatus::Volunteer->value
                        && ! $organization->volunteer_module
                        && $person?->status !== PersonStatus::Volunteer) {
                        $fail('Status volontera nije uključen (Postavke → Kadrovi → Volonteri).');
                    }
                },
            ],
            'fo_kind' => [
                'nullable',
                Rule::enum(OtherFoKind::class),
                Rule::requiredIf(fn () => $request->input('status') === PersonStatus::OtherFo->value),
            ],
            'instrument_title' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => in_array($request->input('status'), [
                    PersonStatus::OtherFo->value,
                    PersonStatus::Contractor->value,
                ], true)),
            ],
            'host_employer' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $request->input('status') === PersonStatus::Assigned->value),
            ],
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
            'legal_entity_id' => [
                'nullable',
                Rule::exists('legal_entities', 'id')->where('organization_id', $organization->id),
            ],
            'work_center_id' => [
                'nullable',
                Rule::exists('work_centers', 'id')->where('organization_id', $organization->id),
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
            'annual_leave_manual' => ['nullable', 'boolean'],
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

        foreach (['location_id', 'department_id', 'job_position_id', 'cost_center_id', 'legal_entity_id', 'work_center_id', 'user_id', 'manager_user_id', 'oib', 'contract_type', 'clock_pin', 'iban', 'family_right', 'tax_relief_note', 'pay_coefficient', 'allowance_percent', 'prior_service_months', 'fo_kind', 'instrument_title', 'host_employer'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        if (! empty($data['iban'])) {
            $data['iban'] = strtoupper(preg_replace('/\s+/', '', (string) $data['iban']));
        }

        $data['znr_exam_required'] = $request->boolean('znr_exam_required');
        $data['annual_leave_manual'] = $request->boolean('annual_leave_manual');
        $data['assignment_clocks'] = $request->boolean('assignment_clocks');
        $data['executive_autonomy'] = $request->boolean('executive_autonomy');
        $data['children_count'] = (int) ($data['children_count'] ?? 0);
        $data['dependents_count'] = (int) ($data['dependents_count'] ?? 0);

        if (! empty($data['job_position_id'])) {
            $position = JobPosition::query()
                ->where('organization_id', $organization->id)
                ->find($data['job_position_id']);
            if ($position) {
                $data['job_title'] = $position->name;
                if ($data['annual_leave_manual'] && (! isset($data['annual_leave_days']) || $data['annual_leave_days'] === null)) {
                    $data['annual_leave_days'] = $position->annual_leave_days;
                }
            }
        }

        if (! empty($data['department_id']) && empty($data['manager_user_id'])) {
            $department = Department::query()
                ->where('organization_id', $organization->id)
                ->with('enterpriseUnit')
                ->find($data['department_id']);
            if ($department?->manager_user_id) {
                $data['manager_user_id'] = $department->manager_user_id;
            }
            if (empty($data['legal_entity_id']) && $department?->enterpriseUnit?->legal_entity_id) {
                $data['legal_entity_id'] = $department->enterpriseUnit->legal_entity_id;
            }
            if (empty($data['work_center_id']) && $department?->enterpriseUnit?->work_center_id) {
                $data['work_center_id'] = $department->enterpriseUnit->work_center_id;
            }
        }

        return $data;
    }

    /**
     * @return list<PersonStatus>
     */
    private function selectableStatuses(\App\Models\Organization $organization, ?Person $person): array
    {
        $statuses = PersonStatus::selectable((bool) $organization->volunteer_module);
        if ($person?->status === PersonStatus::Volunteer && ! in_array(PersonStatus::Volunteer, $statuses, true)) {
            $statuses[] = PersonStatus::Volunteer;
        }

        return $statuses;
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

    private function profileTab(mixed $tab): string
    {
        $allowed = ['pregled', 'odabir', 'osobno', 'zaposlenje', 'angazman', 'ugovori', 'dokumenti', 'kvalifikacije', 'place'];

        return is_string($tab) && in_array($tab, $allowed, true) ? $tab : 'pregled';
    }
}
