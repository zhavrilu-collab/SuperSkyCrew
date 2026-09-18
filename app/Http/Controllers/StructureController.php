<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Services\DepartmentScopeService;
use App\Services\OrganizationRbacService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StructureController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly DepartmentScopeService $scope,
    ) {}

    public function index(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $on = Carbon::parse($request->input('na', now()->toDateString()), config('app.timezone'))->startOfDay();
        $departments = $this->scope->departmentsOn($organization, $on);
        $positions = $this->scope->positionsOn($organization, $on);
        $costCenters = $this->scope->costCentersOn($organization, $on);

        $people = Person::query()
            ->forOrganization($organization)
            ->whereIn('status', ['employee', 'assigned', 'other_fo', 'contractor', 'executive'])
            ->with(['department', 'jobPosition', 'costCenter'])
            ->orderBy('last_name')
            ->get();

        $managers = OrganizationUser::query()
            ->with('user')
            ->where('organization_id', $organization->id)
            ->whereIn('role', ['owner', 'hr', 'manager'])
            ->get()
            ->map(fn (OrganizationUser $membership) => $membership->user)
            ->filter()
            ->unique('id')
            ->values();

        return view('organization.structure.index', [
            'organization' => $organization,
            'on' => $on,
            'departments' => $departments,
            'positions' => $positions,
            'costCenters' => $costCenters,
            'people' => $people,
            'managers' => $managers,
        ]);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $this->validatedDepartment($request);
        $data['organization_id'] = $organization->id;
        Department::query()->create($data);

        return back()->with('status', 'Odjel je spremljen.');
    }

    public function updateDepartment(Request $request, string $slug, Department $department): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($department->organization_id === $organization->id, 404);

        $department->update($this->validatedDepartment($request, $department));

        return back()->with('status', 'Odjel je ažuriran.');
    }

    public function destroyDepartment(string $slug, Department $department): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($department->organization_id === $organization->id, 404);

        if ($department->people()->exists()) {
            return back()->withErrors(['department' => 'Odjel ima dodijeljene osobe. Premjestite ih prije brisanja.']);
        }

        $department->delete();

        return back()->with('status', 'Odjel je obrisan.');
    }

    public function storePosition(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $this->validatedPosition($request);
        $data['organization_id'] = $organization->id;
        JobPosition::query()->create($data);

        return back()->with('status', 'Radno mjesto je spremljeno.');
    }

    public function updatePosition(Request $request, string $slug, JobPosition $position): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($position->organization_id === $organization->id, 404);

        $position->update($this->validatedPosition($request));

        return back()->with('status', 'Radno mjesto je ažurirano.');
    }

    public function destroyPosition(string $slug, JobPosition $position): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($position->organization_id === $organization->id, 404);

        if ($position->people()->exists()) {
            return back()->withErrors(['position' => 'Radno mjesto ima dodijeljene osobe. Premjestite ih prije brisanja.']);
        }

        $position->delete();

        return back()->with('status', 'Radno mjesto je obrisano.');
    }

    public function storeCostCenter(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $this->validatedCostCenter($request);
        $data['organization_id'] = $organization->id;
        CostCenter::query()->create($data);

        return back()->with('status', 'Mjesto troška je spremljeno.');
    }

    public function updateCostCenter(Request $request, string $slug, CostCenter $costCenter): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($costCenter->organization_id === $organization->id, 404);

        $costCenter->update($this->validatedCostCenter($request, $costCenter));

        return back()->with('status', 'Mjesto troška je ažurirano.');
    }

    public function destroyCostCenter(string $slug, CostCenter $costCenter): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($costCenter->organization_id === $organization->id, 404);

        if ($costCenter->people()->exists()) {
            return back()->withErrors(['cost_center' => 'Mjesto troška ima dodijeljene osobe. Premjestite ih prije brisanja.']);
        }

        $costCenter->delete();

        return back()->with('status', 'Mjesto troška je obrisano.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedDepartment(Request $request, ?Department $department = null): array
    {
        $organization = app('currentOrganization');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('departments', 'code')
                    ->where('organization_id', $organization->id)
                    ->ignore($department?->id),
            ],
            'manager_user_id' => [
                'nullable',
                Rule::exists('organization_users', 'user_id')->where('organization_id', $organization->id),
            ],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        foreach (['code', 'manager_user_id', 'valid_from', 'valid_to'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPosition(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'rad1g' => ['nullable', 'string', 'max:16'],
            'annual_leave_days' => ['nullable', 'integer', 'min:0', 'max:50'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        foreach (['rad1g', 'annual_leave_days', 'valid_from', 'valid_to'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCostCenter(Request $request, ?CostCenter $costCenter = null): array
    {
        $organization = app('currentOrganization');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('cost_centers', 'code')
                    ->where('organization_id', $organization->id)
                    ->ignore($costCenter?->id),
            ],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        foreach (['valid_from', 'valid_to'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        return $data;
    }
}
