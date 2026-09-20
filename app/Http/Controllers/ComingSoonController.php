<?php

namespace App\Http\Controllers;

use App\Services\OrganizationRbacService;
use App\Support\ComingSoonCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ComingSoonController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function show(string $slug, string $modul): View
    {
        $spec = ComingSoonCatalog::get($modul);
        abort_unless($spec !== null, 404);

        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        return view('organization.coming-soon', [
            'organization' => $organization,
            'title' => $spec['title'],
            'nav' => $spec['nav'],
            'lead' => $spec['lead'],
        ]);
    }
}
