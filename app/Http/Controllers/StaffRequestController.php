<?php

namespace App\Http\Controllers;

use App\Enums\RequestType;
use App\Models\AbsenceCode;
use App\Models\Person;
use App\Models\Punch;
use App\Models\WorkflowRequest;
use App\Services\LeaveService;
use App\Services\OrganizationRbacService;
use App\Services\WorkflowEngine;
use App\Support\PersonalDataChange;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffRequestController extends Controller
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly LeaveService $leave,
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function index(): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'requests.submit');
        $person = $this->ownPerson();

        $requests = WorkflowRequest::query()
            ->forOrganization($organization)
            ->when(
                $person && ! $this->rbac->can($organization->id, (int) Auth::id(), 'people.access'),
                fn ($query) => $query->where('person_id', $person->id),
            )
            ->with('person')
            ->latest()
            ->get();

        return view('organization.requests.index', [
            'organization' => $organization,
            'requests' => $requests,
            'person' => $person,
            'leave' => $person ? $this->leave->snapshot($person) : null,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'requests.submit');
        $person = $this->ownPerson();

        if ($person === null) {
            return redirect()
                ->route('organization.dashboard', $organization->slug)
                ->withErrors(['clock' => 'Nemate povezanu karticu radnika za podnošenje zahtjeva.']);
        }

        return view('organization.requests.form', [
            'organization' => $organization,
            'person' => $person,
            'currentData' => PersonalDataChange::current($person),
            'selectedType' => request('type'),
            'leave' => $this->leave->snapshot($person),
            'absenceCodes' => AbsenceCode::query()
                ->forOrganization($organization)
                ->where('code', '!=', 'GO')
                ->where('kind', '!=', 'presence')
                ->orderBy('code')
                ->get(),
            'punches' => Punch::query()
                ->where('person_id', $person->id)
                ->whereDoesntHave('corrections')
                ->where('occurred_at_device', '>=', now()->subDays(21))
                ->orderByDesc('occurred_at_device')
                ->limit(40)
                ->get(),
            'selectedPunchId' => request('punch_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'requests.submit');
        $person = $this->ownPerson();
        abort_if($person === null, 403, 'Nemate povezanu karticu radnika za podnošenje zahtjeva.');

        $data = $request->validate([
            'type' => ['required', Rule::enum(RequestType::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'note' => ['nullable', 'string', 'max:255'],
            'absence_code' => ['nullable', 'string', 'max:16'],
            'minutes' => ['nullable', 'integer', 'min:15', 'max:720'],
            'punch_id' => ['nullable', 'integer'],
            'occurred_at' => ['nullable', 'date'],
            'occurred_on' => ['nullable', 'date'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'oib' => ['nullable', 'string', 'max:11'],
            'gender' => ['nullable', 'string', 'max:8'],
            'date_of_birth' => ['nullable', 'date'],
            'citizenship' => ['nullable', 'string', 'max:80'],
            'residence' => ['nullable', 'string', 'max:255'],
        ]);

        $created = $this->engine->submit(
            $organization,
            $person,
            $request->user(),
            RequestType::from($data['type']),
            [
                'from' => $data['from'] ?? null,
                'to' => $data['to'] ?? null,
                'note' => $data['note'] ?? null,
                'absence_code' => $data['absence_code'] ?? null,
                'minutes' => isset($data['minutes']) ? (int) $data['minutes'] : null,
                'punch_id' => isset($data['punch_id']) ? (int) $data['punch_id'] : null,
                'occurred_at' => $data['occurred_at'] ?? null,
                'occurred_on' => $data['occurred_on'] ?? null,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'oib' => $data['oib'] ?? null,
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'citizenship' => $data['citizenship'] ?? null,
                'residence' => $data['residence'] ?? null,
            ],
        );

        return redirect()
            ->route('organization.requests.show', [$organization->slug, $created])
            ->with('status', 'Zahtjev je poslan na odobrenje.');
    }

    public function show(string $slug, WorkflowRequest $zahtjev): View
    {
        $this->assertVisible($zahtjev);

        return view('organization.requests.show', [
            'organization' => app('currentOrganization'),
            'requestItem' => $zahtjev->load(['person', 'actions.user', 'submittedBy']),
            'canAct' => $this->engine->canAct($zahtjev, Auth::user()),
            'canCancel' => $zahtjev->isPending() && (int) $zahtjev->submitted_by_user_id === (int) Auth::id(),
        ]);
    }

    public function decision(string $slug, WorkflowRequest $zahtjev): View
    {
        $this->assertVisible($zahtjev);
        abort_unless($zahtjev->hasLeaveDecision(), 404);

        $person = $zahtjev->person()->firstOrFail();
        $year = (int) ($zahtjev->payload['decision']['year'] ?? Carbon::parse($zahtjev->fromDate())->year);

        return view('organization.requests.decision', [
            'organization' => app('currentOrganization'),
            'requestItem' => $zahtjev->load(['person', 'submittedBy']),
            'person' => $person,
            'leave' => [
                'year' => $year,
                'remaining' => $zahtjev->payload['decision']['remaining'] ?? $this->leave->snapshot($person, $year)['remaining'],
                'remaining_old' => $zahtjev->payload['decision']['remaining_old'] ?? null,
                'remaining_new' => $zahtjev->payload['decision']['remaining_new'] ?? null,
            ],
        ]);
    }

    public function cancel(string $slug, WorkflowRequest $zahtjev): RedirectResponse
    {
        $this->assertVisible($zahtjev);
        $this->engine->cancel($zahtjev, Auth::user());

        return back()->with('status', 'Zahtjev je poništen.');
    }

    private function ownPerson(): ?Person
    {
        $organization = app('currentOrganization');

        return Person::query()
            ->forOrganization($organization)
            ->where('user_id', Auth::id())
            ->first();
    }

    private function assertVisible(WorkflowRequest $zahtjev): void
    {
        $organization = app('currentOrganization');
        abort_unless($zahtjev->organization_id === $organization->id, 404);

        if ($this->rbac->can($organization->id, (int) Auth::id(), 'requests.approve')
            || $this->rbac->can($organization->id, (int) Auth::id(), 'people.access')) {
            return;
        }

        abort_unless((int) $zahtjev->submitted_by_user_id === (int) Auth::id(), 403);
    }
}
