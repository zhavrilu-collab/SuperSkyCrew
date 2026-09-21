<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Department;
use App\Models\EnterpriseUnit;
use App\Models\JobPosition;
use App\Models\LegalEntity;
use App\Models\WorkCenter;
use App\Services\DepartmentScopeService;
use App\Services\OrganizationRbacService;
use App\Services\OrganizationStructureService;
use App\Support\DepartmentTree;
use App\Support\OrgTree;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StructureController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly DepartmentScopeService $scope,
        private readonly OrganizationStructureService $structure,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        return redirect()->route('organization.settings.index', [
            'slug' => $organization->slug,
            'tab' => 'organizacija',
            'section' => 'ustroj',
            'na' => $request->input('na'),
            'katalog' => $request->input('katalog'),
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
        if ($department->children()->exists()) {
            return back()->withErrors(['department' => 'Odjel ima pododjele. Premjestite ih prije brisanja.']);
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

        $position->update($this->validatedPosition($request, $position));

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

    public function storeLegalEntity(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $this->structure->ensure($organization);

        $data = $this->validatedLegalEntity($request);
        $data['organization_id'] = $organization->id;
        LegalEntity::query()->create($data);

        return back()->with('status', 'Pravna osoba je spremljena.');
    }

    public function updateLegalEntity(Request $request, string $slug, LegalEntity $legalEntity): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($legalEntity->organization_id === $organization->id, 404);

        $legalEntity->update($this->validatedLegalEntity($request, $legalEntity));

        return back()->with('status', 'Pravna osoba je ažurirana.');
    }

    public function destroyLegalEntity(string $slug, LegalEntity $legalEntity): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($legalEntity->organization_id === $organization->id, 404);

        if ($legalEntity->children()->exists()) {
            return back()->withErrors(['legal_entity' => 'Pravna osoba ima podređene zapise. Premjestite ih prije brisanja.']);
        }
        if ($legalEntity->people()->exists() || $legalEntity->workCenters()->exists() || $legalEntity->enterpriseUnits()->exists() || $legalEntity->costCenters()->exists()) {
            return back()->withErrors(['legal_entity' => 'Pravna osoba je u upotrebi. Premjestite veze prije brisanja.']);
        }

        $legalEntity->delete();

        return back()->with('status', 'Pravna osoba je obrisana.');
    }

    public function storeWorkCenter(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $this->structure->ensure($organization);

        $data = $this->validatedWorkCenter($request);
        $data['organization_id'] = $organization->id;
        WorkCenter::query()->create($data);

        return back()->with('status', 'Poslovnica je spremljena.');
    }

    public function updateWorkCenter(Request $request, string $slug, WorkCenter $workCenter): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($workCenter->organization_id === $organization->id, 404);

        $workCenter->update($this->validatedWorkCenter($request, $workCenter));

        return back()->with('status', 'Poslovnica je ažurirana.');
    }

    public function destroyWorkCenter(string $slug, WorkCenter $workCenter): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($workCenter->organization_id === $organization->id, 404);

        if ($workCenter->people()->exists() || $workCenter->enterpriseUnits()->exists()) {
            return back()->withErrors(['work_center' => 'Poslovnica je u upotrebi. Premjestite veze prije brisanja.']);
        }

        $workCenter->delete();

        return back()->with('status', 'Poslovnica je obrisana.');
    }

    public function storeEnterpriseUnit(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $this->validatedEnterpriseUnit($request);
        $data['organization_id'] = $organization->id;
        EnterpriseUnit::query()->create($data);

        return back()->with('status', 'Poslovna jedinica je spremljena.');
    }

    public function updateEnterpriseUnit(Request $request, string $slug, EnterpriseUnit $enterpriseUnit): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($enterpriseUnit->organization_id === $organization->id, 404);

        $enterpriseUnit->update($this->validatedEnterpriseUnit($request, $enterpriseUnit));

        return back()->with('status', 'Poslovna jedinica je ažurirana.');
    }

    public function destroyEnterpriseUnit(string $slug, EnterpriseUnit $enterpriseUnit): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($enterpriseUnit->organization_id === $organization->id, 404);

        if ($enterpriseUnit->children()->exists()) {
            return back()->withErrors(['enterprise_unit' => 'Jedinica ima podređene čvorove. Premjestite ih prije brisanja.']);
        }
        if ($enterpriseUnit->departments()->exists()) {
            return back()->withErrors(['enterprise_unit' => 'Jedinica ima odjele. Premjestite ih prije brisanja.']);
        }
        if (EnterpriseUnit::query()->forOrganization($organization)->where('id', '!=', $enterpriseUnit->id)->doesntExist()) {
            return back()->withErrors(['enterprise_unit' => 'Ne možete obrisati jedinu poslovnu jedinicu.']);
        }

        $enterpriseUnit->delete();

        return back()->with('status', 'Poslovna jedinica je obrisana.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedDepartment(Request $request, ?Department $department = null): array
    {
        $organization = app('currentOrganization');
        $root = $this->structure->ensure($organization);
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
            'parent_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('organization_id', $organization->id),
            ],
            'enterprise_unit_id' => [
                'nullable',
                Rule::exists('enterprise_units', 'id')->where('organization_id', $organization->id),
            ],
            'manager_user_id' => [
                'nullable',
                Rule::exists('organization_users', 'user_id')->where('organization_id', $organization->id),
            ],
            'deputy_user_id' => [
                'nullable',
                Rule::exists('organization_users', 'user_id')->where('organization_id', $organization->id),
            ],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        foreach (['code', 'parent_id', 'enterprise_unit_id', 'manager_user_id', 'deputy_user_id', 'valid_from', 'valid_to'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        if (DepartmentTree::wouldCycle($department?->id, $parentId, Department::query()->forOrganization($organization)->get())) {
            throw ValidationException::withMessages([
                'parent_id' => 'Odjel ne može biti nadređen sam sebi.',
            ]);
        }

        if ($parentId) {
            $parent = Department::query()->forOrganization($organization)->find($parentId);
            if ($parent?->enterprise_unit_id) {
                $data['enterprise_unit_id'] = $parent->enterprise_unit_id;
            }
        }

        $data['enterprise_unit_id'] = $data['enterprise_unit_id'] ?? null;
        $data['enterprise_unit_id'] = $data['enterprise_unit_id'] ?: $root->id;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPosition(Request $request, ?JobPosition $position = null): array
    {
        $organization = app('currentOrganization');
        $raw = $request->input('rad1g');
        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw === '') {
                $request->merge(['rad1g' => null]);
            } elseif (preg_match('/^(\d{4})\b/u', $raw, $match)) {
                $request->merge(['rad1g' => $match[1]]);
            }
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('organization_id', $organization->id),
            ],
            'rad1g' => ['nullable', 'regex:/^\d{4}$/', Rule::exists('nkz_occupations', 'code')],
            'annual_leave_days' => ['nullable', 'integer', 'min:0', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duties' => ['nullable', 'string', 'max:4000'],
            'requirements' => ['nullable', 'string', 'max:4000'],
            'pay_grade' => ['nullable', 'string', 'max:32'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ], [
            'rad1g.regex' => 'RAD1G mora biti četveroznamenkasta skupina iz NKZ-10.',
            'rad1g.exists' => 'Odaberite skupinu zanimanja iz službenog šifrarnika NKZ-10.',
        ]);

        foreach (['department_id', 'rad1g', 'annual_leave_days', 'description', 'duties', 'requirements', 'pay_grade', 'valid_from', 'valid_to'] as $empty) {
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
            'legal_entity_id' => [
                'nullable',
                Rule::exists('legal_entities', 'id')->where('organization_id', $organization->id),
            ],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        foreach (['legal_entity_id', 'valid_from', 'valid_to'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedLegalEntity(Request $request, ?LegalEntity $legalEntity = null): array
    {
        $organization = app('currentOrganization');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('legal_entities', 'code')
                    ->where('organization_id', $organization->id)
                    ->ignore($legalEntity?->id),
            ],
            'oib' => ['nullable', 'digits:11'],
            'parent_id' => [
                'nullable',
                Rule::exists('legal_entities', 'id')->where('organization_id', $organization->id),
            ],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'iban' => ['nullable', 'string', 'max:34'],
            'court' => ['nullable', 'string', 'max:120'],
            'capital' => ['nullable', 'string', 'max:80'],
            'signatories' => ['nullable', 'string', 'max:255'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        foreach (['code', 'oib', 'parent_id', 'street', 'city', 'country', 'iban', 'court', 'capital', 'signatories', 'valid_from', 'valid_to'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        if (OrgTree::wouldCycle($legalEntity?->id, $parentId, LegalEntity::query()->forOrganization($organization)->get())) {
            throw ValidationException::withMessages([
                'parent_id' => 'Pravna osoba ne može biti nadređena samoj sebi.',
            ]);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedWorkCenter(Request $request, ?WorkCenter $workCenter = null): array
    {
        $organization = app('currentOrganization');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('work_centers', 'code')
                    ->where('organization_id', $organization->id)
                    ->ignore($workCenter?->id),
            ],
            'legal_entity_id' => [
                'nullable',
                Rule::exists('legal_entities', 'id')->where('organization_id', $organization->id),
            ],
            'location_id' => [
                'nullable',
                Rule::exists('locations', 'id')->where('organization_id', $organization->id),
            ],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        foreach (['code', 'legal_entity_id', 'location_id', 'street', 'city', 'valid_from', 'valid_to'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedEnterpriseUnit(Request $request, ?EnterpriseUnit $unit = null): array
    {
        $organization = app('currentOrganization');
        $this->structure->ensure($organization);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => [
                'nullable',
                Rule::exists('enterprise_units', 'id')->where('organization_id', $organization->id),
            ],
            'legal_entity_id' => [
                'nullable',
                Rule::exists('legal_entities', 'id')->where('organization_id', $organization->id),
            ],
            'work_center_id' => [
                'nullable',
                Rule::exists('work_centers', 'id')->where('organization_id', $organization->id),
            ],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        foreach (['parent_id', 'legal_entity_id', 'work_center_id', 'valid_from', 'valid_to'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        if (OrgTree::wouldCycle($unit?->id, $parentId, EnterpriseUnit::query()->forOrganization($organization)->get())) {
            throw ValidationException::withMessages([
                'parent_id' => 'Jedinica ne može biti nadređena samoj sebi.',
            ]);
        }

        return $data;
    }
}
