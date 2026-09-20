<?php

namespace App\Http\Controllers;

use App\Models\EmploymentContract;
use App\Services\OrganizationRbacService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContractRegisterController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function index(): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $contracts = EmploymentContract::query()
            ->forOrganization($organization)
            ->with('person')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();

        return view('organization.contracts.index', [
            'organization' => $organization,
            'contracts' => $contracts,
        ]);
    }
}
