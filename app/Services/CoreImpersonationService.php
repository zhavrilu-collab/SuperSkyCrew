<?php

namespace App\Services;

use App\Support\AdminConsoleHttp;
use App\Support\CoreApiUrl;

class CoreImpersonationService
{
    /**
     * @return array<string, mixed>|null
     */
    public function fetchSession(string $token): ?array
    {
        $response = AdminConsoleHttp::client()
            ->timeout(8)
            ->get(CoreApiUrl::endpoint('/platform/impersonation/'.$token));

        if (! $response->successful()) {
            return null;
        }

        $session = $response->json('session');

        return is_array($session) ? $session : null;
    }

    public function endSession(string $token): void
    {
        AdminConsoleHttp::client()
            ->timeout(8)
            ->post(CoreApiUrl::endpoint('/platform/impersonation/'.$token.'/end'));
    }
}
