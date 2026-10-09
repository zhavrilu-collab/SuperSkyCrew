<?php

namespace App\Http\Controllers;

use App\Enums\CompetencyKind;
use App\Models\Competency;
use App\Models\JobPosition;
use App\Services\OrganizationRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CompetencyController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function index(): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        return $this->toOpisi();
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('competencies', 'name')->where('organization_id', $organization->id)],
            'kind' => ['required', Rule::enum(CompetencyKind::class)],
        ]);
        Competency::query()->create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'kind' => $data['kind'],
        ]);

        return $this->toOpisi((int) $request->input('mjesto'))->with('status', 'Kompetencija je dodana u šifrarnik.');
    }

    public function destroy(string $slug, Competency $competency): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($competency->organization_id === $organization->id, 404);
        $competency->jobPositions()->detach();
        $competency->delete();

        return $this->toOpisi()->with('status', 'Kompetencija je uklonjena.');
    }

    public function attach(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $data = $request->validate([
            'job_position_id' => ['required', Rule::exists('job_positions', 'id')->where('organization_id', $organization->id)],
            'competency_id' => ['required', Rule::exists('competencies', 'id')->where('organization_id', $organization->id)],
            'required_level' => ['required', 'integer', 'min:1', 'max:5'],
        ]);
        $job = JobPosition::query()->forOrganization($organization)->findOrFail($data['job_position_id']);
        $job->competencies()->syncWithoutDetaching([
            $data['competency_id'] => [
                'organization_id' => $organization->id,
                'required_level' => $data['required_level'],
            ],
        ]);

        return $this->toOpisi($job->id)->with('status', 'Kompetencija je vezana na radno mjesto.');
    }

    public function detach(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $data = $request->validate([
            'job_position_id' => ['required', Rule::exists('job_positions', 'id')->where('organization_id', $organization->id)],
            'competency_id' => ['required', Rule::exists('competencies', 'id')->where('organization_id', $organization->id)],
        ]);
        $job = JobPosition::query()->forOrganization($organization)->findOrFail($data['job_position_id']);
        $job->competencies()->detach($data['competency_id']);

        return $this->toOpisi($job->id)->with('status', 'Veza je uklonjena.');
    }

    private function toOpisi(?int $mjesto = null): RedirectResponse
    {
        $organization = app('currentOrganization');

        return redirect()->route('organization.systematization.index', array_filter([
            'slug' => $organization->slug,
            'mjesto' => $mjesto ?: null,
        ]));
    }
}
