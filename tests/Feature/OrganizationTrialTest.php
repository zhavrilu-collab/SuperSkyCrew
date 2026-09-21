<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\OrganizationTrialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTrialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['admin_sync.api_key' => 'test-sync-key']);
    }

    public function test_activation_starts_standard_trial(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'trial-activate',
            'status' => OrganizationStatus::Pending,
            'plan' => 'basic',
        ]);

        $this->withToken('test-sync-key')
            ->patchJson('/api/admin/organizations/'.$organization->id, [
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.plan', 'standard');

        $organization->refresh();
        $this->assertNotNull($organization->trial_ends_at);
        $this->assertTrue($organization->onTrial());
        $this->assertSame('standard', $organization->plan);
        $this->assertSame(100, $organization->employee_limit);
        $this->assertGreaterThanOrEqual(13, $organization->trialDaysRemaining());
    }

    public function test_activation_with_explicit_premium_keeps_premium(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'trial-premium',
            'status' => OrganizationStatus::Pending,
            'plan' => 'basic',
        ]);

        $this->withToken('test-sync-key')
            ->patchJson('/api/admin/organizations/'.$organization->id, [
                'status' => 'active',
                'plan' => 'premium',
            ])
            ->assertOk()
            ->assertJsonPath('data.plan', 'premium');

        $organization->refresh();
        $this->assertNotNull($organization->trial_ends_at);
        $this->assertSame('premium', $organization->plan);
        $this->assertSame(500, $organization->employee_limit);
    }

    public function test_activation_with_stripe_skips_trial(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'trial-stripe',
            'status' => OrganizationStatus::Pending,
            'plan' => 'basic',
            'stripe_subscription_id' => 'sub_test',
        ]);

        $this->withToken('test-sync-key')
            ->patchJson('/api/admin/organizations/'.$organization->id, [
                'status' => 'active',
            ])
            ->assertOk();

        $organization->refresh();
        $this->assertNull($organization->trial_ends_at);
        $this->assertSame('basic', $organization->plan);
    }

    public function test_expired_trial_downgrades_to_basic_without_stripe(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'trial-expire',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'employee_limit' => 100,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->assertTrue(app(OrganizationTrialService::class)->expireIfNeeded($organization));
        $organization->refresh();
        $this->assertSame('basic', $organization->plan);
        $this->assertSame(25, $organization->employee_limit);
        $this->assertTrue($organization->trialExpired());
    }

    public function test_tenant_middleware_lazy_expires_trial(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'slug' => 'trial-lazy',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'employee_limit' => 100,
            'trial_ends_at' => now()->subDay(),
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        $this->actingAs($user)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('Probni period je istekao');

        $organization->refresh();
        $this->assertSame('basic', $organization->plan);
    }

    public function test_admin_can_extend_active_trial(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'trial-extend',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'trial_ends_at' => now()->addDays(3),
        ]);
        $originalEnds = $organization->trial_ends_at->copy();

        $this->withToken('test-sync-key')
            ->patchJson('/api/admin/organizations/'.$organization->id, [
                'extend_trial_days' => 14,
            ])
            ->assertOk()
            ->assertJsonPath('data.plan', 'standard');

        $organization->refresh();
        $this->assertTrue($organization->onTrial());
        $this->assertTrue($organization->trial_ends_at->greaterThan($originalEnds->addDays(13)));
        $this->assertGreaterThanOrEqual(16, $organization->trialDaysRemaining());
    }

    public function test_admin_can_restart_expired_trial(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'trial-restart',
            'status' => OrganizationStatus::Active,
            'plan' => 'basic',
            'trial_ends_at' => now()->subDays(2),
        ]);

        $this->withToken('test-sync-key')
            ->patchJson('/api/admin/organizations/'.$organization->id, [
                'extend_trial_days' => 7,
            ])
            ->assertOk()
            ->assertJsonPath('data.plan', 'standard');

        $organization->refresh();
        $this->assertTrue($organization->onTrial());
        $this->assertSame('standard', $organization->plan);
        $this->assertGreaterThanOrEqual(6, $organization->trialDaysRemaining());
    }

    public function test_extend_trial_blocked_when_stripe_subscription_exists(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'trial-stripe-block',
            'status' => OrganizationStatus::Active,
            'plan' => 'premium',
            'trial_ends_at' => now()->addDays(2),
            'stripe_subscription_id' => 'sub_live',
        ]);

        $this->withToken('test-sync-key')
            ->patchJson('/api/admin/organizations/'.$organization->id, [
                'extend_trial_days' => 7,
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Probni period se ne može produljiti dok postoji aktivna Stripe pretplata.',
            ]);
    }
}
