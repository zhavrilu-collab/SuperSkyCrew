<?php

namespace App\Http\Controllers;

use App\Enums\PunchType;
use App\Models\Location;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\Punch;
use App\Models\TimeEntry;
use App\Models\WorkflowRequest;
use App\Services\DepartmentScopeService;
use App\Services\ExpiryWarningService;
use App\Services\LeaveService;
use App\Services\OrganizationRbacService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrganizationDashboardController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly LeaveService $leave,
        private readonly ExpiryWarningService $expiries,
        private readonly DepartmentScopeService $scope,
    ) {}

    public function __invoke(string $slug): View
    {
        $organization = app('currentOrganization');
        $membership = app('currentOrganizationUser');
        $userId = (int) Auth::id();

        $peopleQuery = Person::query()->forOrganization($organization);
        $this->scope->restrictPeopleQuery($peopleQuery, $organization, $userId);
        $scopedPeople = $peopleQuery->get();
        $people = Person::query()->forOrganization($organization)->get();
        $present = Punch::query()
            ->forOrganization($organization)
            ->whereDoesntHave('corrections')
            ->whereIn('person_id', $scopedPeople->pluck('id'))
            ->orderByDesc('occurred_at_device')
            ->orderByDesc('id')
            ->get()
            ->unique('person_id')
            ->filter(fn (Punch $punch) => in_array($punch->type, [PunchType::In, PunchType::BreakStart, PunchType::BreakEnd], true))
            ->count();

        $ownPerson = $people->first(fn (Person $person) => (int) $person->user_id === $userId);
        $pendingApprovals = 0;
        if ($this->rbac->can($organization->id, $userId, 'requests.approve')) {
            $pendingApprovals = WorkflowRequest::query()
                ->forOrganization($organization)
                ->where('status', 'pending')
                ->count();
        }

        return view('organization.dashboard', [
            'organization' => $organization,
            'membership' => $membership,
            'canManageTeam' => $this->rbac->can($organization->id, $userId, 'team.manage'),
            'canAccessPeople' => $this->rbac->can($organization->id, $userId, 'people.access'),
            'canAccessTime' => $this->rbac->can($organization->id, $userId, 'time.access'),
            'canExportPayroll' => $this->rbac->can($organization->id, $userId, 'payroll.export'),
            'canApprove' => $this->rbac->can($organization->id, $userId, 'requests.approve'),
            'teamCount' => OrganizationUser::query()->where('organization_id', $organization->id)->count(),
            'peopleCount' => $people->count(),
            'presentCount' => $present,
            'hasOwnPerson' => $ownPerson !== null,
            'ownPerson' => $ownPerson,
            'leave' => $ownPerson ? $this->leave->snapshot($ownPerson) : null,
            'pendingApprovals' => $pendingApprovals,
            'expiryCount' => $this->rbac->can($organization->id, $userId, 'people.access')
                ? count($this->expiries->due($organization))
                : 0,
            'exceptionCount' => ($this->rbac->can($organization->id, $userId, 'time.access')
                || $this->rbac->can($organization->id, $userId, 'payroll.export'))
                ? TimeEntry::query()
                    ->forOrganization($organization)
                    ->openExceptions()
                    ->whereIn('person_id', $scopedPeople->pluck('id'))
                    ->whereDate('work_date', '>=', now()->subDays(7)->toDateString())
                    ->whereDate('work_date', '<=', now()->toDateString())
                    ->count()
                : 0,
            'kioskLocation' => Location::query()
                ->forOrganization($organization)
                ->where('kiosk_enabled', true)
                ->whereNotNull('kiosk_token')
                ->where('is_active', true)
                ->first(),
            'absentToday' => TimeEntry::query()
                ->forOrganization($organization)
                ->whereDate('work_date', now()->toDateString())
                ->whereNotNull('absence_code')
                ->when(
                    ! $this->rbac->can($organization->id, $userId, 'time.access')
                    && ! $this->rbac->can($organization->id, $userId, 'people.access')
                    && ! $this->rbac->can($organization->id, $userId, 'payroll.export')
                    && ! $this->rbac->can($organization->id, $userId, 'requests.approve'),
                    fn ($query) => $ownPerson
                        ? $query->where('person_id', $ownPerson->id)
                        : $query->whereRaw('1 = 0'),
                )
                ->when(
                    $this->rbac->can($organization->id, $userId, 'time.access')
                    && ! $this->scope->seesAllPeople($organization->id, $userId),
                    fn ($query) => $query->whereIn('person_id', $scopedPeople->pluck('id')),
                )
                ->pluck('person_id')
                ->unique()
                ->count(),
        ]);
    }
}
