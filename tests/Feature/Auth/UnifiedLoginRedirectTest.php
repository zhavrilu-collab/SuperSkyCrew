<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class UnifiedLoginRedirectTest extends TestCase
{
    public function test_login_page_stays_on_hr_app_when_core_unified_is_enabled(): void
    {
        config([
            'identity.core_auth_enabled' => true,
            'identity.unified_login_enabled' => true,
            'identity.core_api_url' => 'http://127.0.0.1:8001',
            'identity.application_slug' => 'hr-saas',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Prijava — SuperSkyCrew', false)
            ->assertSee('Nemate tvrtku?')
            ->assertDontSee('Jedinstvena prijava');
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
