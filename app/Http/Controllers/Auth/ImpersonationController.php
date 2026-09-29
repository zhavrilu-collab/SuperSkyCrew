<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Services\CoreImpersonationService;
use App\Support\ImpersonationSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function __construct(
        private readonly CoreImpersonationService $coreImpersonation,
    ) {}

    public function enter(string $token): RedirectResponse
    {
        $session = $this->coreImpersonation->fetchSession($token);

        if ($session === null) {
            abort(404, 'Impersonacija nije valjana ili je istekla.');
        }

        $tenant = $session['tenant'] ?? [];
        $organization = Organization::query()
            ->whereKey((int) ($tenant['external_id'] ?? 0))
            ->first();

        if ($organization === null && isset($tenant['slug'])) {
            $organization = Organization::query()
                ->where('slug', $tenant['slug'])
                ->first();
        }

        if ($organization === null) {
            abort(404, 'Tvrtka za ovu impersonaciju nije pronađena.');
        }

        $orgUser = OrganizationUser::query()
            ->with('user')
            ->where('organization_id', $organization->id)
            ->whereIn('role', [OrganizationRole::Owner, OrganizationRole::Hr])
            ->orderByRaw("CASE WHEN role = ? THEN 0 ELSE 1 END", [OrganizationRole::Owner->value])
            ->first();

        if ($orgUser?->user === null) {
            abort(403, 'Tvrtka nema aktivnog vlasnika ili HR korisnika za impersonaciju.');
        }

        Auth::login($orgUser->user);
        request()->session()->regenerate();

        $admin = $session['admin'] ?? [];

        ImpersonationSession::store([
            'token' => $token,
            'name' => (string) ($admin['name'] ?? 'administrator platforme'),
            'email' => (string) ($admin['email'] ?? ''),
            'expires_at' => $session['expires_at'] ?? null,
        ]);

        return redirect()
            ->route('organization.landing', ['slug' => $organization->slug])
            ->with('status', 'Impersonacija je aktivna.');
    }

    public function exit(): RedirectResponse
    {
        $this->endCoreSession();

        ImpersonationSession::clear();

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Impersonacija je završena.');
    }

    public function endCoreSession(): void
    {
        $token = ImpersonationSession::token();

        if (is_string($token) && $token !== '') {
            $this->coreImpersonation->endSession($token);
        }
    }
}
