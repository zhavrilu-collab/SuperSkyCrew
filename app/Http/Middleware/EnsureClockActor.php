<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClockActor
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');
        if (! is_string($slug)) {
            abort(404);
        }

        $organization = Organization::query()->where('slug', $slug)->first();
        if ($organization === null || $organization->status !== OrganizationStatus::Active) {
            abort(404);
        }

        $request->attributes->set('currentOrganization', $organization);
        app()->instance('currentOrganization', $organization);

        $bearer = $request->bearerToken() ?: $request->input('clock_token');
        if (is_string($bearer) && $bearer !== '') {
            $person = Person::query()
                ->forOrganization($organization)
                ->where('clock_api_token', $bearer)
                ->first();

            if ($person === null || ! $person->isClockEligible()) {
                return $this->deny($request, 401, 'Clock token nije valjan.');
            }

            $request->attributes->set('clockPerson', $person);
            app()->instance('currentClockPerson', $person);

            return $next($request);
        }

        $user = $request->user();
        if ($user === null) {
            return $this->deny($request, 401, 'Prijava za Clock API nije valjana.');
        }

        $membership = OrganizationUser::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            return $this->deny($request, 403, 'Nemate pristup ovoj tvrtki.');
        }

        $request->attributes->set('currentOrganizationUser', $membership);
        app()->instance('currentOrganizationUser', $membership);

        $person = Person::query()
            ->forOrganization($organization)
            ->where('user_id', $user->id)
            ->first();
        if ($person !== null) {
            $request->attributes->set('clockPerson', $person);
            app()->instance('currentClockPerson', $person);
        }

        return $next($request);
    }

    private function deny(Request $request, int $status, string $message): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], $status);
        }

        abort($status, $message);
    }
}
