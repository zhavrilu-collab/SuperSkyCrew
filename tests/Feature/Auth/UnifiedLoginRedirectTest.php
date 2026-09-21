<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class UnifiedLoginRedirectTest extends TestCase
{
    public function test_login_page_redirects_to_core_when_unified_login_enabled(): void
    {
        config([
            'identity.core_auth_enabled' => true,
            'identity.unified_login_enabled' => true,
            'identity.core_api_url' => 'http://127.0.0.1:8001',
            'identity.application_slug' => 'hr-saas',
        ]);

        $this->get(route('login'))
            ->assertRedirect('http://127.0.0.1:8001/platform/prijava?application_slug=hr-saas');
    }

    public function test_local_login_shows_register_and_core_password_reset(): void
    {
        config([
            'identity.core_auth_enabled' => true,
            'identity.unified_login_enabled' => false,
            'identity.core_api_url' => 'http://127.0.0.1:8001',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Prijava')
            ->assertSee('Nemate tvrtku?')
            ->assertSee('http://127.0.0.1:8001/zaboravljena-lozinka', false)
            ->assertSee('Zaboravili ste lozinku?');
    }
}
