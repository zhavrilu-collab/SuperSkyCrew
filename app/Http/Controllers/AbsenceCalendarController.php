<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\AbsenceCode;
use App\Models\Person;
use App\Models\TimeEntry;
use App\Models\WorkflowRequest;
use App\Services\DepartmentScopeService;
use App\Services\OrganizationRbacService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AbsenceCalendarController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly DepartmentScopeService $scope,
    ) {}

    public function index(Request $request): View
    {
        $organization = app('currentOrganization');
        $userId = (int) Auth::id();
        $canViewTeam = $this->canViewTeam($organization->id, $userId);

        $month = Carbon::parse($request->input('month', now()->format('Y-m')).'-01', config('app.timezone'))->startOfMonth();
        $from = $month->copy();
        $to = $month->copy()->endOfMonth();
        $codeFilter = $request->input('code');
        $departmentFilter = $request->input('department');

        $peopleQuery = Person::query()
            ->forOrganization($organization)
            ->with('department')
            ->clockEligible()
            ->orderBy('last_name')
            ->orderBy('first_name');

        if (! $canViewTeam) {
            $peopleQuery->where('user_id', $userId);
        } else {
            $this->scope->restrictPeopleQuery($peopleQuery, $organization, $userId);
        }

        if (is_string($departmentFilter) && $departmentFilter !== '') {
            $peopleQuery->where('department_id', (int) $departmentFilter);
        }

        $people = $peopleQuery->get();
        $personIds = $people->pluck('id');

        $codes = AbsenceCode::query()
            ->forOrganization($organization)
            ->orderBy('code')
            ->get()
            ->keyBy('code');

        $entries = TimeEntry::query()
            ->whereIn('person_id', $personIds)
            ->whereNotNull('absence_code')
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->get();

        /** @var array<int, array<string, array{code: string, pending: bool, request_id: int|null}>> $cells */
        $cells = [];
        foreach ($entries as $entry) {
            $date = $entry->work_date->toDateString();
            $cells[$entry->person_id][$date] = [
                'code' => $entry->absence_code,
                'pending' => false,
                'request_id' => null,
            ];
        }

        $pending = WorkflowRequest::query()
            ->whereIn('person_id', $personIds)
            ->where('status', RequestStatus::Pending)
            ->whereIn('type', [RequestType::LeaveAnnual->value, RequestType::LeaveOther->value])
            ->get();

        foreach ($pending as $item) {
            $code = $item->absenceCode() ?: 'GO';
            foreach ($item->payload['dates'] ?? [] as $date) {
                if ($date < $from->toDateString() || $date > $to->toDateString()) {
                    continue;
                }
                if (isset($cells[$item->person_id][$date])) {
                    continue;
                }
                $cells[$item->person_id][$date] = [
                    'code' => $code,
                    'pending' => true,
                    'request_id' => $item->id,
                ];
            }
        }

        if (is_string($codeFilter) && $codeFilter !== '') {
            $people = $people->filter(function (Person $person) use ($cells, $codeFilter) {
                foreach ($cells[$person->id] ?? [] as $cell) {
                    if ($cell['code'] === $codeFilter) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $days = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $days[] = $day->copy();
        }

        $today = now()->timezone(config('app.timezone'))->toDateString();
        $absentToday = TimeEntry::query()
            ->forOrganization($organization)
            ->whereDate('work_date', $today)
            ->whereNotNull('absence_code')
            ->when(! $canViewTeam, fn ($query) => $query->whereIn('person_id', $personIds))
            ->when($canViewTeam && ! $this->scope->seesAllPeople($organization->id, $userId), fn ($query) => $query->whereIn('person_id', $personIds))
            ->pluck('person_id')
            ->unique()
            ->count();

        $monthNames = [1 => 'siječanj', 2 => 'veljača', 3 => 'ožujak', 4 => 'travanj', 5 => 'svibanj', 6 => 'lipanj', 7 => 'srpanj', 8 => 'kolovoz', 9 => 'rujan', 10 => 'listopad', 11 => 'studeni', 12 => 'prosinac'];
        $departments = collect();
        if ($canViewTeam) {
            $departments = $this->scope->departmentsOn($organization, $month->copy());
            if (! $this->scope->seesAllPeople($organization->id, $userId)) {
                $managed = $this->scope->managedDepartmentIds($organization, $userId);
                $departments = $departments->filter(
                    fn ($department) => in_array((int) $department->id, $managed, true)
                )->values();
            }
        }

        return view('organization.absences.calendar', [
            'organization' => $organization,
            'people' => $people,
            'days' => $days,
            'month' => $month,
            'monthLabel' => $monthNames[$month->month].' '.$month->year.'.',
            'prev' => $month->copy()->subMonth(),
            'next' => $month->copy()->addMonth(),
            'cells' => $cells,
            'codes' => $codes,
            'codeFilter' => $codeFilter,
            'departmentFilter' => $departmentFilter,
            'departments' => $departments,
            'canViewTeam' => $canViewTeam,
            'today' => $today,
            'absentToday' => $absentToday,
            'weekdays' => [1 => 'P', 2 => 'U', 3 => 'S', 4 => 'Č', 5 => 'P', 6 => 'S', 7 => 'N'],
        ]);
    }

    private function canViewTeam(int $organizationId, int $userId): bool
    {
        return $this->rbac->can($organizationId, $userId, 'time.access')
            || $this->rbac->can($organizationId, $userId, 'people.access')
            || $this->rbac->can($organizationId, $userId, 'payroll.export')
            || $this->rbac->can($organizationId, $userId, 'requests.approve');
    }
}
