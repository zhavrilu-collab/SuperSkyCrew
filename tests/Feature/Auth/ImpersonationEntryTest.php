<?php

namespace Tests\Feature\Auth;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Support\ImpersonationSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImpersonationEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'identity.core_api_url' => 'http://core.test',
        ]);
    }

    public function test_support_token_logs_in_owner_and_shows_banner(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'impersonation-tvrtka',
        ]);

        Http::fake([
            'http://core.test/api/v1/platform/impersonation/support-token-123' => Http::response([
                'session' => [
                    'admin' => [
                        'name' => 'Super Admin',
                        'email' => 'admin@example.com',
                    ],
                    'tenant' => [
                        'external_id' => (string) $organization->id,
                        'slug' => $organization->slug,
                        'name' => $organization->name,
                    ],
                    'expires_at' => now()->addMinutes(30)->toIso8601String(),
                ],
            ]),
            'http://core.test/api/v1/platform/impersonation/support-token-123/end' => Http::response([
                'message' => 'Impersonation sesija je završena.',
            ]),
        ]);

        $owner = User::factory()->create(['email' => 'owner@test.hr']);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => OrganizationRole::Owner,
        ]);

        $this->get('/impersonacija/support-token-123')
            ->assertRedirect(route('organization.landing', $organization->slug));

        $this->assertAuthenticatedAs($owner);
        $this->assertTrue(ImpersonationSession::isActive());

        $this->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('Impersonacija — Super Admin')
            ->assertSee('Završi impersonaciju');

        $this->post('/impersonacija/izlaz')
            ->assertRedirect(route('login', [], false));

        $this->assertGuest();
    }

    public function test_unknown_token_returns_not_found(): void
    {
        Http::fake([
            'http://core.test/api/v1/platform/impersonation/*' => Http::response(['message' => 'Not found'], 404),
        ]);

        $this->get('/impersonacija/nepostojeci-token')
            ->assertNotFound();
    }

    public function test_expired_impersonation_session_is_cleared_on_next_request(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'expired-impersonation-tvrtka',
        ]);

        Http::fake([
            'http://core.test/api/v1/platform/impersonation/expired-token/end' => Http::response([
                'message' => 'Impersonation sesija je završena.',
            ]),
        ]);

        $owner = User::factory()->create(['email' => 'owner@test.hr']);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => OrganizationRole::Owner,
        ]);

        $this->withSession([
            ImpersonationSession::TOKEN => 'expired-token',
            ImpersonationSession::ADMIN_NAME => 'Super Admin',
            ImpersonationSession::ADMIN_EMAIL => 'admin@example.com',
            ImpersonationSession::EXPIRES_AT => now()->subMinute()->toIso8601String(),
        ])->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertRedirect(route('login', [], false));

        $this->assertGuest();
    }
}
