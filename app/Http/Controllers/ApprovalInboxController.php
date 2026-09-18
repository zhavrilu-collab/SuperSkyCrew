<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\WorkflowRequest;
use App\Services\OrganizationRbacService;
use App\Services\WorkflowEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApprovalInboxController extends Controller
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function index(): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'requests.approve');

        $pending = WorkflowRequest::query()
            ->forOrganization($organization)
            ->where('status', RequestStatus::Pending)
            ->with('person')
            ->latest()
            ->get()
            ->filter(fn (WorkflowRequest $item) => $this->engine->canAct($item, Auth::user()))
            ->values();

        return view('organization.approvals.index', [
            'organization' => $organization,
            'pending' => $pending,
        ]);
    }

    public function approve(Request $request, string $slug, WorkflowRequest $zahtjev): RedirectResponse
    {
        $this->assertOrg($zahtjev);
        $this->engine->approve($zahtjev, $request->user(), $request->input('comment'));

        return redirect()
            ->route('organization.approvals.index', $slug)
            ->with('status', 'Zahtjev je odobren.');
    }

    public function reject(Request $request, string $slug, WorkflowRequest $zahtjev): RedirectResponse
    {
        $this->assertOrg($zahtjev);
        $data = $request->validate([
            'comment' => ['required', 'string', 'max:255'],
        ]);
        $this->engine->reject($zahtjev, $request->user(), $data['comment']);

        return redirect()
            ->route('organization.approvals.index', $slug)
            ->with('status', 'Zahtjev je odbijen.');
    }

    private function assertOrg(WorkflowRequest $zahtjev): void
    {
        abort_unless($zahtjev->organization_id === app('currentOrganization')->id, 404);
    }
}
