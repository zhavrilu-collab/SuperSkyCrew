<?php

namespace App\Http\Controllers;

use App\Enums\CalendarLevel;
use App\Models\CalendarRule;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Person;
use App\Models\Shift;
use App\Services\DepartmentScopeService;
use App\Services\OrganizationRbacService;
use App\Services\ShiftResolver;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly DepartmentScopeService $scope,
        private readonly ShiftResolver $resolver,
    ) {}

    public function index(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->authorizeView($organization->id);
        $userId = (int) Auth::id();
        $canManage = $this->rbac->can($organization->id, $userId, 'people.access');

        $from = Carbon::parse($request->input('from', now()->startOfWeek(Carbon::MONDAY)->toDateString()), config('app.timezone'))
            ->startOfWeek(Carbon::MONDAY);
        $to = $from->copy()->endOfWeek(Carbon::SUNDAY);

        $peopleQuery = Person::query()
            ->forOrganization($organization)
            ->with(['department', 'jobPosition'])
            ->whereIn('status', ['employee', 'assigned', 'other_fo', 'contractor', 'executive'])
            ->orderBy('last_name')
            ->orderBy('first_name');
        $this->scope->restrictPeopleQuery($peopleQuery, $organization, $userId);
        $people = $peopleQuery->get();

        $days = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $days[] = $day->copy();
        }

        return view('organization.schedule.index', [
            'organization' => $organization,
            'canManage' => $canManage,
            'shifts' => Shift::query()->forOrganization($organization)->orderBy('starts_at')->orderBy('name')->get(),
            'rules' => CalendarRule::query()
                ->forOrganization($organization)
                ->with(['shift', 'department', 'jobPosition', 'person'])
                ->orderBy('level')
                ->orderBy('weekday')
                ->get(),
            'departments' => Department::query()->forOrganization($organization)->orderBy('name')->get(),
            'positions' => JobPosition::query()->forOrganization($organization)->orderBy('name')->get(),
            'people' => $people,
            'levels' => CalendarLevel::cases(),
            'weekdays' => [1 => 'Pon', 2 => 'Uto', 3 => 'Sri', 4 => 'Čet', 5 => 'Pet', 6 => 'Sub', 7 => 'Ned'],
            'weekdayNames' => [1 => 'Ponedjeljak', 2 => 'Utorak', 3 => 'Srijeda', 4 => 'Četvrtak', 5 => 'Petak', 6 => 'Subota', 7 => 'Nedjelja'],
            'from' => $from,
            'to' => $to,
            'prev' => $from->copy()->subWeek(),
            'next' => $from->copy()->addWeek(),
            'days' => $days,
            'plan' => $this->resolver->mapForPeople($people, $from, $to),
        ]);
    }

    public function storeShift(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->authorizeManage($organization->id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:16'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'is_night' => ['nullable', 'boolean'],
        ]);
        $data['organization_id'] = $organization->id;
        $data['break_minutes'] = (int) ($data['break_minutes'] ?? 0);
        $data['is_night'] = $request->boolean('is_night');
        if (($data['code'] ?? '') === '') {
            $data['code'] = null;
        }
        $data['starts_at'] .= ':00';
        $data['ends_at'] .= ':00';

        Shift::query()->create($data);

        return back()->with('status', 'Smjena je spremljena.');
    }

    public function destroyShift(string $slug, Shift $shift): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->authorizeManage($organization->id);
        abort_unless($shift->organization_id === $organization->id, 404);

        if ($shift->rules()->exists()) {
            return back()->withErrors(['shift' => 'Smjena je na kalendaru. Uklonite pravila prije brisanja.']);
        }

        $shift->delete();

        return back()->with('status', 'Smjena je obrisana.');
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->authorizeManage($organization->id);

        $data = $request->validate([
            'level' => ['required', Rule::enum(CalendarLevel::class)],
            'weekday' => ['required', 'integer', 'min:1', 'max:7'],
            'shift_id' => [
                'nullable',
                Rule::exists('shifts', 'id')->where('organization_id', $organization->id),
            ],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('organization_id', $organization->id),
            ],
            'job_position_id' => [
                'nullable',
                Rule::exists('job_positions', 'id')->where('organization_id', $organization->id),
            ],
            'person_id' => [
                'nullable',
                Rule::exists('people', 'id')->where('organization_id', $organization->id),
            ],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $level = CalendarLevel::from($data['level']);
        if ($level === CalendarLevel::Department && empty($data['department_id'])) {
            return back()->withErrors(['department_id' => 'Odaberite odjel.'])->withInput();
        }
        if ($level === CalendarLevel::JobPosition && empty($data['job_position_id'])) {
            return back()->withErrors(['job_position_id' => 'Odaberite radno mjesto.'])->withInput();
        }
        if ($level === CalendarLevel::Person && empty($data['person_id'])) {
            return back()->withErrors(['person_id' => 'Odaberite osobu.'])->withInput();
        }

        foreach (['shift_id', 'department_id', 'job_position_id', 'person_id', 'valid_from', 'valid_to'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        if ($level === CalendarLevel::Organization) {
            $data['department_id'] = null;
            $data['job_position_id'] = null;
            $data['person_id'] = null;
        } elseif ($level === CalendarLevel::Department) {
            $data['job_position_id'] = null;
            $data['person_id'] = null;
        } elseif ($level === CalendarLevel::JobPosition) {
            $data['department_id'] = null;
            $data['person_id'] = null;
        } else {
            $data['department_id'] = null;
            $data['job_position_id'] = null;
        }

        CalendarRule::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'level' => $level->value,
                'weekday' => $data['weekday'],
                'department_id' => $data['department_id'],
                'job_position_id' => $data['job_position_id'],
                'person_id' => $data['person_id'],
            ],
            [
                'shift_id' => $data['shift_id'] ?? null,
                'valid_from' => $data['valid_from'] ?? null,
                'valid_to' => $data['valid_to'] ?? null,
            ],
        );

        return back()->with('status', 'Pravilo kalendara je spremljeno. Specifičnija razina pobjeđuje.');
    }

    public function destroyRule(string $slug, CalendarRule $rule): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->authorizeManage($organization->id);
        abort_unless($rule->organization_id === $organization->id, 404);
        $rule->delete();

        return back()->with('status', 'Pravilo je obrisano.');
    }

    private function authorizeView(int $organizationId): void
    {
        $userId = (int) Auth::id();
        if (
            $this->rbac->can($organizationId, $userId, 'time.access')
            || $this->rbac->can($organizationId, $userId, 'people.access')
            || $this->rbac->can($organizationId, $userId, 'payroll.export')
        ) {
            return;
        }

        abort(403, 'Nemate ovlasti za ovu radnju.');
    }

    private function authorizeManage(int $organizationId): void
    {
        $this->rbac->authorize($organizationId, (int) Auth::id(), 'people.access');
    }
}
