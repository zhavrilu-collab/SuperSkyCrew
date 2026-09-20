<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class OrganizationLandingController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $organization = app('currentOrganization');

        return redirect()->route('organization.dashboard', $organization->slug);
    }
}
