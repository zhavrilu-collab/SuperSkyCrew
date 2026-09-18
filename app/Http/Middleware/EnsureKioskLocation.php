<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Models\Location;
use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKioskLocation
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');
        $token = $request->route('token');

        if (! is_string($slug) || ! is_string($token)) {
            abort(404);
        }

        $organization = Organization::query()->where('slug', $slug)->first();
        if ($organization === null || $organization->status !== OrganizationStatus::Active) {
            abort(404);
        }

        $location = Location::query()
            ->where('organization_id', $organization->id)
            ->where('kiosk_token', $token)
            ->where('kiosk_enabled', true)
            ->where('is_active', true)
            ->first();

        if ($location === null) {
            abort(404);
        }

        $request->attributes->set('currentOrganization', $organization);
        $request->attributes->set('currentKioskLocation', $location);
        app()->instance('currentOrganization', $organization);
        app()->instance('currentKioskLocation', $location);

        return $next($request);
    }
}
