<?php

namespace App\Http\Controllers;

use App\Services\ExpiryWarningService;
use App\Services\OrganizationRbacService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExpiryController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly ExpiryWarningService $expiries,
    ) {}

    public function index(): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $items = $this->expiries->due($organization);

        return view('organization.expiries.index', [
            'organization' => $organization,
            'items' => $items,
        ]);
    }
}
