<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('admin@nbaynouk.test|127.0.0.1');
        parent::tearDown();
    }

    public function test_internal_routes_require_authentication(): void
    {
        foreach (['/dashboard', '/projects', '/clients', '/payments', '/billing', '/team', '/services', '/settings', '/search?q=test'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }

    public function test_login_cannot_be_bypassed_with_sql_injection_payloads(): void
    {
        User::factory()->create(['email' => 'admin@nbaynouk.test', 'password' => 'correct-password']);

        foreach (["' OR 1=1 --", "admin' --", "' OR '1'='1"] as $payload) {
            $this->post('/login', ['email' => $payload, 'password' => $payload])->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        User::factory()->create(['email' => 'admin@nbaynouk.test', 'password' => 'correct-password']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/login', ['email' => 'admin@nbaynouk.test', 'password' => 'wrong-password'])->assertUnprocessable();
        }

        $this->postJson('/login', ['email' => 'admin@nbaynouk.test', 'password' => 'wrong-password'])->assertStatus(429);
    }

    public function test_login_regenerates_session_and_logout_invalidates_it(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);
        $this->post('/login', ['email' => $user->email, 'password' => 'correct-password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
