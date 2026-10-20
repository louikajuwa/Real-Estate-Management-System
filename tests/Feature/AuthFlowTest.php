<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_register_creates_client_user_and_crm_record(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Buyer',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user, 'User row should be created');
        $this->assertTrue($user->hasRole('client'), 'New registrant should get the client role');
        $this->assertDatabaseHas('clients', ['user_id' => $user->id]);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Dup',
            'email' => 'taken@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422);
    }

    public function test_staff_login_rejects_a_client_account(): void
    {
        $user = User::factory()->create([
            'email' => 'client@example.com',
            'password' => 'secret123',
            'is_active' => true,
        ]);
        $user->assignRole('client');
        Client::create(['user_id' => $user->id, 'name' => 'Client', 'email' => 'client@example.com']);

        // Client tries to sign in through the staff portal -> 403
        $this->postJson('/api/staff/login', [
            'email' => 'client@example.com',
            'password' => 'secret123',
        ])->assertStatus(403);
    }

    public function test_client_login_succeeds(): void
    {
        $user = User::factory()->create([
            'email' => 'client2@example.com',
            'password' => 'secret123',
            'is_active' => true,
        ]);
        $user->assignRole('client');

        $this->postJson('/api/login', [
            'email' => 'client2@example.com',
            'password' => 'secret123',
        ])->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_inactive_user_is_blocked_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'secret123',
            'is_active' => false,
        ]);
        $user->assignRole('client');

        $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'secret123',
        ])->assertStatus(403);
    }
}
