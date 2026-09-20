<?php

namespace App\Http\Controllers;

use App\Models\BusinessSegment;
use App\Models\EnterpriseUnit;
use App\Services\OrganizationRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessSegmentController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function index(): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        return view('organization.segments.index', [
            'organization' => $organization,
            'segments' => BusinessSegment::query()->forOrganization($organization)->orderBy('name')->get(),
            'units' => EnterpriseUnit::query()->forOrganization($organization)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $data = $this->validated($request);
        $data['organization_id'] = $organization->id;
        BusinessSegment::query()->create($data);

        return back()->with('status', 'Poslovni segment je spremljen.');
    }

    public function update(Request $request, string $slug, BusinessSegment $segment): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($segment->organization_id === $organization->id, 404);
        $segment->update($this->validated($request, $segment));

        return back()->with('status', 'Poslovni segment je ažuriran.');
    }

    public function destroy(string $slug, BusinessSegment $segment): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($segment->organization_id === $organization->id, 404);
        EnterpriseUnit::query()->where('business_segment_id', $segment->id)->update(['business_segment_id' => null]);
        $segment->delete();

        return back()->with('status', 'Poslovni segment je uklonjen.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?BusinessSegment $segment = null): array
    {
        $organization = app('currentOrganization');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('business_segments', 'code')
                    ->where('organization_id', $organization->id)
                    ->ignore($segment?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        foreach (['code', 'description', 'valid_from', 'valid_to'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        return $data;
    }
}
