<?php

namespace App\Http\Middleware;

use App\Services\CoreImpersonationService;
use App\Support\ImpersonationSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveImpersonationSession
{
    public function __construct(
        private readonly CoreImpersonationService $coreImpersonation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! ImpersonationSession::isActive()) {
            return $next($request);
        }

        if (! ImpersonationSession::isExpired()) {
            return $next($request);
        }

        $token = ImpersonationSession::token();

        if (is_string($token) && $token !== '') {
            $this->coreImpersonation->endSession($token);
        }

        ImpersonationSession::clear();
        Auth::logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->route('login')
            ->with('status', 'Impersonacija je istekla.');
    }
}
