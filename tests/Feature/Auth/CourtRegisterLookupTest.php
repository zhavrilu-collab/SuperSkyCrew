<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourtRegisterLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_returns_empty_when_sudreg_is_not_configured(): void
    {
        config([
            'sudreg.client_id' => null,
            'sudreg.client_secret' => null,
        ]);

        $this->getJson(route('register.organization.court-register', ['q' => 'Končar']))
            ->assertOk()
            ->assertJsonPath('meta.configured', false)
            ->assertJsonPath('results', []);
    }

    public function test_lookup_by_oib_uses_sudreg_api(): void
    {
        config([
            'sudreg.client_id' => 'id..',
            'sudreg.client_secret' => 'secret..',
            'sudreg.base_url' => 'https://sudreg-data.gov.hr/api/javni',
            'sudreg.token_url' => 'https://sudreg-data.gov.hr/api/oauth/token',
        ]);

        Http::fake([
            'https://sudreg-data.gov.hr/api/oauth/token' => Http::response([
                'access_token' => 'tok',
                'expires_in' => 3600,
            ]),
            'https://sudreg-data.gov.hr/api/javni/detalji_subjekta*' => Http::response([
                'potpuni_oib' => '12345678903',
                'potpuni_mbs' => '080000001',
                'glavna_djelatnost' => 6201,
                'tvrtka' => ['ime' => 'Končar d.d.', 'naznaka_imena' => 'Končar'],
                'sjediste' => [
                    'ulica' => 'Fallerovo šetalište',
                    'kucni_broj' => 22,
                    'naziv_naselja' => 'Zagreb',
                    'postanski_broj' => 10000,
                ],
                'email_adrese' => [
                    ['adresa' => 'info@koncar.hr'],
                ],
            ]),
        ]);

        $this->getJson(route('register.organization.court-register', ['q' => '12345678903']))
            ->assertOk()
            ->assertJsonPath('meta.configured', true)
            ->assertJsonPath('results.0.name', 'Končar d.d.')
            ->assertJsonPath('results.0.oib', '12345678903')
            ->assertJsonPath('results.0.mbs', '080000001')
            ->assertJsonPath('results.0.city', 'Zagreb')
            ->assertJsonPath('results.0.email', 'info@koncar.hr');
    }

    public function test_registration_form_mentions_court_register_and_trial(): void
    {
        $this->get(route('register.organization'))
            ->assertOk()
            ->assertSee('Sudski registar')
            ->assertSee('14 dana')
            ->assertSee('Preporučeno / trial');
    }
}
