<?php

namespace App\Http\Controllers;

use App\Enums\CompetencyKind;
use App\Enums\InternalActKind;
use App\Models\Competency;
use App\Models\InternalAct;
use App\Models\Person;
use App\Services\DepartmentScopeService;
use App\Services\OrganizationRbacService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SystematizationController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly DepartmentScopeService $scope,
    ) {}

    public function index(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $pogled = $request->input('pogled') === 'akti' ? 'akti' : 'opisi';
        $on = Carbon::parse($request->input('na', now()->toDateString()))->startOfDay();
        $q = trim((string) $request->input('q', ''));

        $data = [
            'organization' => $organization,
            'pogled' => $pogled,
            'on' => $on,
            'q' => $q,
            'positions' => collect(),
            'filled' => collect(),
            'selectedPosition' => null,
            'competencies' => collect(),
            'competencyKinds' => CompetencyKind::cases(),
            'acts' => collect(),
            'actKinds' => InternalActKind::cases(),
        ];

        if ($pogled === 'akti') {
            $data['acts'] = InternalAct::query()
                ->forOrganization($organization)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->get();

            return view('organization.systematization.index', $data);
        }

        $positions = $this->scope->positionsOn($organization, $on)->load(['department.enterpriseUnit', 'competencies']);
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $positions = $positions->filter(function ($position) use ($needle) {
                return str_contains(mb_strtolower($position->name), $needle)
                    || str_contains(mb_strtolower((string) $position->rad1g), $needle)
                    || str_contains(mb_strtolower((string) $position->rad1gLabel()), $needle)
                    || str_contains(mb_strtolower((string) $position->department?->name), $needle);
            })->values();
        }

        $mjestoId = (int) $request->input('mjesto');
        $selectedPosition = $mjestoId > 0
            ? $positions->firstWhere('id', $mjestoId)
            : $positions->first();

        $positionIds = $positions->pluck('id')->filter();
        $filled = $positionIds->isEmpty()
            ? collect()
            : Person::query()
                ->forOrganization($organization)
                ->whereIn('job_position_id', $positionIds)
                ->where(function ($query) use ($on) {
                    $query->whereNull('started_at')->orWhereDate('started_at', '<=', $on->toDateString());
                })
                ->where(function ($query) use ($on) {
                    $query->whereNull('ended_at')->orWhereDate('ended_at', '>=', $on->toDateString());
                })
                ->selectRaw('job_position_id, COUNT(*) as filled_count')
                ->groupBy('job_position_id')
                ->pluck('filled_count', 'job_position_id');

        $data['positions'] = $positions;
        $data['filled'] = $filled;
        $data['selectedPosition'] = $selectedPosition;
        $data['competencies'] = Competency::query()->forOrganization($organization)->orderBy('name')->get();

        return view('organization.systematization.index', $data);
    }
}
