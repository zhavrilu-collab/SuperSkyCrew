<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_uses_the_crew_login_shell(): void
    {
        config([
            'identity.core_auth_enabled' => false,
        ]);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Pošalji poveznicu')
            ->assertSee('SuperSkyCrew', false)
            ->assertSee('login-logo', false)
            ->assertDontSee('superskycontrol', false);
    }

    public function test_local_password_can_be_reset(): void
    {
        Notification::fake();
        config(['identity.core_auth_enabled' => false]);

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'nova-lozinka',
                'password_confirmation' => 'nova-lozinka',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('nova-lozinka', $user->fresh()->password));
    }

    public function test_core_password_reset_stays_on_crew_and_calls_the_platform(): void
    {
        config([
            'identity.core_auth_enabled' => true,
            'identity.core_api_url' => 'http://127.0.0.1:8001',
            'identity.application_slug' => 'hr-saas',
        ]);

        $this->app->setLocale('hr');

        Http::fake([
            'http://127.0.0.1:8001/api/v1/auth/forgot-password' => Http::response([
                'status' => 'passwords.sent',
                'message' => 'Poslali smo vam poveznicu za novu lozinku.',
            ]),
        ]);

        $this->post(route('password.email'), ['email' => 'ana@example.com'])
            ->assertSessionHas('status', 'Poslali smo vam poveznicu za novu lozinku.');

        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:8001/api/v1/auth/forgot-password'
                && $request['application_slug'] === 'hr-saas'
                && $request['email'] === 'ana@example.com';
        });
    }
}
