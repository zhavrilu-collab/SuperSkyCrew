<?php

namespace App\Http\Controllers;

use App\Models\GrantEntry;
use App\Models\GrantProject;
use App\Models\Person;
use App\Services\FeatureService;
use App\Services\OrganizationRbacService;
use App\Support\OrganizationFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GrantHoursController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly FeatureService $features,
    ) {}

    public function index(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'time.access');
        $this->features->assertEnabled($organization, OrganizationFeatures::GRANT_HOURS);

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        return view('organization.grants.index', [
            'organization' => $organization,
            'projects' => GrantProject::query()->forOrganization($organization)->orderBy('code')->get(),
            'people' => Person::query()->forOrganization($organization)->orderBy('last_name')->orderBy('first_name')->get(),
            'entries' => GrantEntry::query()
                ->forOrganization($organization)
                ->with(['person', 'project'])
                ->whereDate('work_date', '>=', $from)
                ->whereDate('work_date', '<=', $to)
                ->orderByDesc('work_date')
                ->orderByDesc('id')
                ->limit(200)
                ->get(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function storeProject(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $this->features->assertEnabled($organization, OrganizationFeatures::GRANT_HOURS);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('grant_projects', 'code')->where('organization_id', $organization->id),
            ],
            'name' => ['required', 'string', 'max:160'],
        ]);

        GrantProject::query()->create([
            'organization_id' => $organization->id,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'is_active' => true,
        ]);

        return back()->with('status', 'Projekt je spremljen. Sati ne ulaze u zakonski slog.');
    }

    public function storeEntry(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'time.access');
        $this->features->assertEnabled($organization, OrganizationFeatures::GRANT_HOURS);

        $data = $request->validate([
            'person_id' => [
                'required',
                Rule::exists('people', 'id')->where('organization_id', $organization->id),
            ],
            'grant_project_id' => [
                'required',
                Rule::exists('grant_projects', 'id')->where('organization_id', $organization->id),
            ],
            'work_date' => ['required', 'date'],
            'minutes' => ['required', 'integer', 'min:15', 'max:720'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        GrantEntry::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $data['person_id'],
            'grant_project_id' => $data['grant_project_id'],
            'work_date' => $data['work_date'],
            'minutes' => $data['minutes'],
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('status', 'Grant sati su upisani. Zakonski slog (TimeEntry) nije diran.');
    }

    public function destroyEntry(string $slug, GrantEntry $entry): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'time.access');
        abort_unless($entry->organization_id === $organization->id, 404);
        $entry->delete();

        return back()->with('status', 'Grant slog je obrisan.');
    }
}
