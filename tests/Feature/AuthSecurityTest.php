<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function register(string $email, ?string $redirect)
    {
        return $this->postJson('/api/register', array_filter([
            'name' => 'Jane',
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'redirect' => $redirect,
        ], fn ($v) => $v !== null));
    }

    public function test_register_rejects_unsafe_redirects(): void
    {
        $unsafe = [
            'javascript:alert(1)',
            'JaVaScRiPt:alert(1)',
            '//evil.example',
            'https://evil.example/x',
            '/\\evil.example',
            "/\t/evil.example",
            "/\n/evil.example",
            'evil.example',
        ];

        foreach ($unsafe as $i => $redirect) {
            $this->register("unsafe{$i}@example.com", $redirect)
                ->assertStatus(201)
                ->assertJsonPath('data.redirect', '/');
        }
    }

    public function test_register_keeps_safe_relative_redirects(): void
    {
        $this->register('safe@example.com', '/properties?city=Dubai')
            ->assertStatus(201)
            ->assertJsonPath('data.redirect', '/properties?city=Dubai');
    }

    public function test_register_defaults_redirect_to_root(): void
    {
        $this->register('none@example.com', null)->assertJsonPath('data.redirect', '/');
    }

    public function test_login_is_rate_limited(): void
    {
        $payload = ['email' => 'victim@example.com', 'password' => 'wrong-password'];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', $payload)->assertStatus(401);
        }
        $this->postJson('/api/login', $payload)->assertStatus(429);
    }

    public function test_staff_login_is_rate_limited(): void
    {
        $payload = ['email' => 'staff@example.com', 'password' => 'wrong-password'];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/staff/login', $payload)->assertStatus(401);
        }
        $this->postJson('/api/staff/login', $payload)->assertStatus(429);
    }

    public function test_register_is_rate_limited(): void
    {
        $payload = ['name' => 'x', 'email' => 'dup@example.com', 'password' => 'short', 'password_confirmation' => 'nope'];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/register', $payload)->assertStatus(422);
        }
        $this->postJson('/api/register', $payload)->assertStatus(429);
    }

    public function test_rate_limit_is_per_email_and_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'x'])->assertStatus(401);
        }
        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'x'])->assertStatus(429);
        // A different account is not locked out by someone hammering another email.
        $this->postJson('/api/login', ['email' => 'b@example.com', 'password' => 'x'])->assertStatus(401);
    }

    public function test_chat_send_is_throttled(): void
    {
        $user = $this->makeClientUser();
        $this->actingAs($user);

        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/chat', [])->assertStatus(422);
        }
        $this->postJson('/api/chat', [])->assertStatus(429);
    }

    public function test_root_route_is_behind_the_active_middleware(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);
        $inactive->assignRole('client');

        $this->actingAs($inactive)->get('/')->assertRedirect(route('login'));
    }

    public function test_socket_url_is_exposed_through_config(): void
    {
        $this->assertArrayHasKey('url', config('services.socket'));
    }
}
