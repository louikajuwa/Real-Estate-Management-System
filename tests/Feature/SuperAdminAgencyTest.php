<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Client;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAgencyTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->super = User::factory()->create(['is_active' => true, 'agency_id' => null]);
        $this->super->assignRole('super_admin');
    }

    public function test_super_admin_creates_agency_and_gets_generated_password(): void
    {
        $res = $this->actingAs($this->super)->postJson('/api/super/agencies', [
            'name' => 'New Realty',
            'admin_name' => 'New Admin',
            'admin_email' => 'newadmin@example.com',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.admin_email', 'newadmin@example.com');

        // A temp password is generated and returned for the super admin to share.
        $this->assertNotEmpty($res->json('data.admin_password'));

        $this->assertDatabaseHas('agencies', ['name' => 'New Realty']);
        $admin = User::where('email', 'newadmin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertNotNull($admin->agency_id);
    }

    public function test_super_admin_deletes_agency_and_its_staff(): void
    {
        $create = $this->actingAs($this->super)->postJson('/api/super/agencies', [
            'name' => 'Doomed Realty',
            'admin_name' => 'Doomed Admin',
            'admin_email' => 'doomed@example.com',
        ]);
        $slug = $create->json('data.agency.slug');
        $this->assertNotNull($slug);

        $this->actingAs($this->super)->deleteJson('/api/super/agencies/'.$slug)
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('agencies', ['slug' => $slug]);
        $this->assertDatabaseMissing('users', ['email' => 'doomed@example.com']);
    }

    public function test_agency_detail_returns_listings_and_customers(): void
    {
        $create = $this->actingAs($this->super)->postJson('/api/super/agencies', [
            'name' => 'Detail Realty', 'admin_name' => 'D', 'admin_email' => 'detail@example.com',
        ]);
        $agency = Agency::where('slug', $create->json('data.agency.slug'))->first();

        $agent = Agent::create(['agency_id' => $agency->id, 'user_id' => User::factory()->create()->id]);
        Property::create([
            'agency_id' => $agency->id, 'agent_id' => $agent->id, 'title' => 'Detail Listing',
            'slug' => 'detail-listing', 'description' => null, 'type' => 'villa', 'price' => 2000000,
            'address' => 'x', 'city' => 'Dubai', 'status' => 'available', 'is_featured' => false,
        ]);
        Client::create(['agency_id' => $agency->id, 'name' => 'Detail Customer']);

        $res = $this->actingAs($this->super)->getJson('/api/super/agencies/'.$agency->slug);
        $res->assertStatus(200)
            ->assertJsonPath('data.properties.0.title', 'Detail Listing')
            ->assertJsonPath('data.clients.0.name', 'Detail Customer')
            ->assertJsonPath('data.clients_count', 1);
    }

    public function test_super_admin_updates_agency_info(): void
    {
        $create = $this->actingAs($this->super)->postJson('/api/super/agencies', [
            'name' => 'Edit Me Realty', 'admin_name' => 'A', 'admin_email' => 'editme@example.com',
        ]);
        $slug = $create->json('data.agency.slug');

        $this->actingAs($this->super)->putJson('/api/super/agencies/'.$slug, [
            'name' => 'Edited Realty', 'plan_type' => 'pro', 'city' => 'Abu Dhabi',
        ])->assertStatus(200);

        $this->assertDatabaseHas('agencies', ['slug' => $slug, 'name' => 'Edited Realty', 'plan_type' => 'pro', 'city' => 'Abu Dhabi']);
    }

    public function test_super_admin_resets_agency_admin_password(): void
    {
        $create = $this->actingAs($this->super)->postJson('/api/super/agencies', [
            'name' => 'Reset Realty', 'admin_name' => 'B', 'admin_email' => 'resetme@example.com',
        ]);
        $slug = $create->json('data.agency.slug');
        $oldHash = User::where('email', 'resetme@example.com')->first()->password;

        $res = $this->actingAs($this->super)->postJson('/api/super/agencies/'.$slug.'/reset-admin-password');
        $res->assertStatus(200)->assertJsonPath('data.admin_email', 'resetme@example.com');
        $this->assertNotEmpty($res->json('data.admin_password'));

        $newHash = User::where('email', 'resetme@example.com')->first()->password;
        $this->assertNotSame($oldHash, $newHash, 'Password hash should change after reset');
    }

    public function test_non_super_admin_cannot_manage_agencies(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->getJson('/api/super/agencies')->assertStatus(403);
    }
}
