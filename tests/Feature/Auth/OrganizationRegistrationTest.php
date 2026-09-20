<?php

namespace Tests\Feature\Auth;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Jobs\NotifyAdminConsoleJob;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrganizationRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'identity.core_auth_enabled' => false,
            'admin_console.webhook_url' => null,
        ]);
    }

    public function test_guest_can_open_company_registration_form(): void
    {
        $this->get(route('register.organization'))
            ->assertOk()
            ->assertSee('Registracija tvrtke')
            ->assertSee('SuperSkyCrew')
            ->assertSee('Osnovni')
            ->assertSee('Standardni')
            ->assertSee('Premium')
            ->assertSee('Vlasnički račun');
    }

    public function test_guest_can_register_pending_organization(): void
    {
        $response = $this->post(route('register.organization'), [
            'name' => 'Nova tvrtka d.o.o.',
            'oib' => '12345678903',
            'organization_email' => 'office@nova-tvrtka.hr',
            'phone' => '0912345678',
            'city' => 'Zagreb',
            'plan' => 'standard',
            'organization_type' => OrganizationType::Company->value,
            'admin_name' => 'Ana Vlasnik',
            'admin_email' => 'ana@nova-tvrtka.hr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('registration.pending'));

        $this->assertDatabaseHas('organizations', [
            'name' => 'Nova tvrtka d.o.o.',
            'status' => OrganizationStatus::Pending->value,
            'email' => 'office@nova-tvrtka.hr',
            'oib' => '12345678903',
            'plan' => 'standard',
            'employee_limit' => 100,
            'organization_type' => OrganizationType::Company->value,
            'volunteer_module' => 0,
        ]);

        $user = User::query()->where('email', 'ana@nova-tvrtka.hr')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        $organization = Organization::query()->where('name', 'Nova tvrtka d.o.o.')->first();
        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner->value,
        ]);
        $this->assertDatabaseHas('legal_entities', [
            'organization_id' => $organization->id,
            'name' => 'Nova tvrtka d.o.o.',
            'oib' => '12345678903',
        ]);
        $this->assertDatabaseHas('absence_codes', [
            'organization_id' => $organization->id,
            'code' => 'GO',
        ]);
    }

    public function test_nonprofit_registration_enables_volunteer_module(): void
    {
        $this->post(route('register.organization'), [
            'name' => 'Sportska udruga',
            'oib' => '69435151556',
            'organization_email' => 'info@sportska.hr',
            'organization_type' => OrganizationType::Nonprofit->value,
            'plan' => 'basic',
            'admin_name' => 'Iva Admin',
            'admin_email' => 'iva@sportska.hr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('registration.pending'));

        $this->assertDatabaseHas('organizations', [
            'name' => 'Sportska udruga',
            'organization_type' => OrganizationType::Nonprofit->value,
            'volunteer_module' => 1,
            'plan' => 'basic',
            'employee_limit' => 25,
        ]);
    }

    public function test_logged_in_user_can_register_another_company(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('register.organization'), [
                'name' => 'Druga tvrtka d.o.o.',
                'oib' => '69435151556',
                'organization_email' => 'office@druga.hr',
                'plan' => 'premium',
            ])
            ->assertRedirect(route('registration.pending'));

        $organization = Organization::query()->where('name', 'Druga tvrtka d.o.o.')->first();
        $this->assertNotNull($organization);
        $this->assertSame('premium', $organization->plan);
        $this->assertSame(500, $organization->employee_limit);
        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner->value,
        ]);
    }

    public function test_registration_rejects_invalid_oib(): void
    {
        $this->post(route('register.organization'), [
            'name' => 'Krivi OIB d.o.o.',
            'oib' => '12345678901',
            'organization_email' => 'office@krivi-oib.hr',
            'admin_name' => 'Test User',
            'admin_email' => 'test@krivi-oib.hr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('oib');
    }

    public function test_registration_dispatches_webhook_when_configured(): void
    {
        Queue::fake();

        config([
            'admin_console.webhook_url' => 'http://127.0.0.1:8001/api/webhooks/tenants/registered',
            'admin_console.webhook_secret' => 'test-secret',
            'admin_console.application_slug' => 'hr-saas',
        ]);

        $this->post(route('register.organization'), [
            'name' => 'Webhook tvrtka d.o.o.',
            'oib' => '12345678903',
            'organization_email' => 'office@webhook.hr',
            'admin_name' => 'Webhook User',
            'admin_email' => 'webhook@webhook.hr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('registration.pending'));

        Queue::assertPushed(NotifyAdminConsoleJob::class);
    }
}
