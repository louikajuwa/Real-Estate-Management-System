<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Client;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class AgencyDeletionTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_deleting_an_agency_removes_trashed_properties_agents_and_image_files(): void
    {
        Storage::fake('public');
        $super = $this->makeSuperAdmin();
        $agency = Agency::factory()->create();
        $admin = $this->makeAdmin($agency);
        $agentUser = $this->makeAgent($agency);

        $live = Property::factory()->create(['agency_id' => $agency->id, 'agent_id' => $agentUser->agent->id]);
        $trashed = Property::factory()->create(['agency_id' => $agency->id, 'agent_id' => $agentUser->agent->id]);
        Storage::disk('public')->put('properties/live.jpg', 'x');
        Storage::disk('public')->put('properties/trashed.jpg', 'x');
        PropertyImage::factory()->create(['property_id' => $live->id, 'path' => 'properties/live.jpg']);
        PropertyImage::factory()->create(['property_id' => $trashed->id, 'path' => 'properties/trashed.jpg']);
        $trashed->delete();

        $customer = Client::factory()->create(['agency_id' => $agency->id]);

        $this->actingAs($super)->deleteJson("/api/super/agencies/{$agency->slug}")
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseMissing('agencies', ['id' => $agency->id]);
        $this->assertSame(0, Property::withoutGlobalScope('agency')->withTrashed()->count());
        $this->assertSame(0, PropertyImage::count());
        $this->assertSame(0, Agent::withoutGlobalScope('agency')->count());
        $this->assertNull(User::find($agentUser->id));
        $this->assertNull(User::find($admin->id));
        Storage::disk('public')->assertMissing('properties/live.jpg');
        Storage::disk('public')->assertMissing('properties/trashed.jpg');

        // Platform customers survive, detached from the agency.
        $this->assertNull($customer->fresh()->agency_id);
    }

    public function test_deleting_an_agency_leaves_other_agencies_untouched(): void
    {
        $super = $this->makeSuperAdmin();
        $doomed = Agency::factory()->create();
        $other = Agency::factory()->create();
        $keep = Property::factory()->create(['agency_id' => $other->id]);

        $this->actingAs($super)->deleteJson("/api/super/agencies/{$doomed->slug}")->assertOk();

        $this->assertNotNull(Property::withoutGlobalScope('agency')->find($keep->id));
        $this->assertNotNull(Agency::find($other->id));
    }

    public function test_agent_with_properties_cannot_be_deleted(): void
    {
        $agency = Agency::factory()->create();
        $admin = $this->makeAdmin($agency);
        $agentUser = $this->makeAgent($agency);
        Property::factory()->create(['agency_id' => $agency->id, 'agent_id' => $agentUser->agent->id]);

        $res = $this->actingAs($admin)->deleteJson("/api/agents/{$agentUser->agent->id}")->assertStatus(422);
        $this->assertStringContainsString('properties', $res->json('message'));
        $this->assertNotNull(Agent::withoutGlobalScope('agency')->find($agentUser->agent->id));
    }

    public function test_agent_with_only_soft_deleted_properties_cannot_be_deleted(): void
    {
        $agency = Agency::factory()->create();
        $admin = $this->makeAdmin($agency);
        $agentUser = $this->makeAgent($agency);
        Property::factory()->create(['agency_id' => $agency->id, 'agent_id' => $agentUser->agent->id])->delete();

        $this->actingAs($admin)->deleteJson("/api/agents/{$agentUser->agent->id}")->assertStatus(422);
    }

    public function test_agent_without_properties_is_deleted(): void
    {
        $agency = Agency::factory()->create();
        $admin = $this->makeAdmin($agency);
        $agentUser = $this->makeAgent($agency);

        $this->actingAs($admin)->deleteJson("/api/agents/{$agentUser->agent->id}")->assertOk();
        $this->assertNull(User::find($agentUser->id));
    }

    public function test_agent_admin_actions_are_forbidden_for_agents_and_other_agencies(): void
    {
        $agency = Agency::factory()->create();
        $agentUser = $this->makeAgent($agency);
        $outsider = $this->makeAdmin();

        $this->actingAs($agentUser)->deleteJson("/api/agents/{$agentUser->agent->id}")->assertStatus(403);
        $this->actingAs($outsider)->deleteJson("/api/agents/{$agentUser->agent->id}")->assertStatus(404);
        $this->actingAs($agentUser)->postJson('/api/agents', [
            'name' => 'x', 'email' => 'x@example.com', 'password' => 'password1',
        ])->assertStatus(403);
    }

    public function test_agent_can_update_only_their_own_profile(): void
    {
        $agency = Agency::factory()->create();
        $me = $this->makeAgent($agency);
        $colleague = $this->makeAgent($agency);

        $this->actingAs($me)->putJson("/api/agents/{$me->agent->id}", ['bio' => 'Updated bio'])->assertOk();
        $this->putJson("/api/agents/{$colleague->agent->id}", ['bio' => 'Hacked'])->assertStatus(403);
    }
}
