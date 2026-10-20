<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_super_admin_can_read_platform_logs_and_agency_admin_only_their_own(): void
    {
        $a = Agency::factory()->create();
        $b = Agency::factory()->create();
        AuditLog::create(['agency_id' => $a->id, 'action' => 'x.a', 'description' => 'a']);
        AuditLog::create(['agency_id' => $b->id, 'action' => 'x.b', 'description' => 'b']);

        $super = $this->makeSuperAdmin();
        $res = $this->actingAs($super)->getJson('/api/logs')->assertOk();
        $this->assertCount(2, $res->json('data'));

        $res = $this->actingAs($this->makeAdmin($a))->getJson('/api/logs')->assertOk();
        $this->assertSame(['x.a'], collect($res->json('data'))->pluck('action')->all());

        $this->actingAs($this->makeClientUser())->getJson('/api/logs')->assertStatus(403);
        $this->actingAs($this->makeAgent($a))->getJson('/api/logs')->assertStatus(403);
    }

    public function test_super_admin_dashboard_shows_platform_wide_numbers(): void
    {
        Property::factory()->count(2)->create();

        $res = $this->actingAs($this->makeSuperAdmin())->getJson('/api/dashboard/stats')->assertOk();
        $this->assertSame(2, $res->json('data.properties.total'));

        $this->getJson('/api/dashboard/charts')->assertOk();
        $this->getJson('/api/dashboard/activity')->assertOk();
    }

    public function test_super_admin_has_no_operational_write_access_to_agency_data(): void
    {
        $property = Property::factory()->create();
        $super = $this->makeSuperAdmin();

        $this->actingAs($super);
        $this->putJson("/api/properties/{$property->id}", ['title' => 'x'])->assertStatus(403);
        $this->deleteJson("/api/properties/{$property->id}")->assertStatus(403);
        $this->getJson("/api/properties/{$property->id}")->assertOk();
    }
}
