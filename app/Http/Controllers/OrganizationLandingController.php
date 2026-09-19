<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Services\OrganizationRbacService;
use Illuminate\View\View;

class OrganizationLandingController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function __invoke(): View
    {
        $organization = app('currentOrganization');
        $membership = app('currentOrganizationUser');
        $userId = (int) auth()->id();
        $isWorker = $membership->role === OrganizationRole::Employee;

        return view('organization.landing', [
            'organization' => $organization,
            'membership' => $membership,
            'isWorker' => $isWorker,
            'canPeople' => $this->rbac->can($organization->id, $userId, 'people.access'),
            'canTime' => $this->rbac->can($organization->id, $userId, 'time.access')
                || $this->rbac->can($organization->id, $userId, 'payroll.export'),
            'canApprove' => $this->rbac->can($organization->id, $userId, 'requests.approve'),
            'canSettings' => $this->rbac->canOpenSettings($organization->id, $userId),
        ]);
    }
}
