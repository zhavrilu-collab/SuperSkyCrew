<?php

namespace App\Http\Controllers;

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

        $on = Carbon::parse($request->input('na', now()->toDateString()))->startOfDay();
        $q = trim((string) $request->input('q', ''));

        $positions = $this->scope->positionsOn($organization, $on)->load('department.enterpriseUnit');
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $positions = $positions->filter(function ($position) use ($needle) {
                return str_contains(mb_strtolower($position->name), $needle)
                    || str_contains(mb_strtolower((string) $position->rad1g), $needle)
                    || str_contains(mb_strtolower((string) $position->rad1gLabel()), $needle)
                    || str_contains(mb_strtolower((string) $position->department?->name), $needle);
            })->values();
        }

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

        return view('organization.systematization.index', [
            'organization' => $organization,
            'on' => $on,
            'q' => $q,
            'positions' => $positions,
            'filled' => $filled,
        ]);
    }
}
