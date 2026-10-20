<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class PropertyRulesTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $agentUser;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $this->agency = Agency::factory()->create();
        $this->admin = $this->makeAdmin($this->agency);
        $this->agentUser = $this->makeAgent($this->agency);
        $this->property = Property::factory()->create([
            'agency_id' => $this->agency->id,
            'agent_id' => $this->agentUser->agent->id,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'New Villa', 'type' => 'villa', 'price' => 900000,
            'address' => '1 Palm Rd', 'city' => 'Dubai',
        ], $overrides);
    }

    public function test_only_admin_can_feature_on_create(): void
    {
        $this->actingAs($this->agentUser)
            ->postJson('/api/properties', $this->payload(['is_featured' => true]))
            ->assertCreated();
        $this->assertFalse((bool) Property::where('title', 'New Villa')->first()->is_featured);

        $this->actingAs($this->admin)
            ->postJson('/api/properties', $this->payload([
                'title' => 'Admin Villa', 'is_featured' => true, 'agent_id' => $this->agentUser->agent->id,
            ]))
            ->assertCreated();
        $this->assertTrue((bool) Property::where('title', 'Admin Villa')->first()->is_featured);
    }

    public function test_general_update_cannot_change_status(): void
    {
        $this->actingAs($this->agentUser)
            ->putJson("/api/properties/{$this->property->id}", ['title' => 'Renamed', 'status' => 'sold'])
            ->assertOk();

        $fresh = $this->property->fresh();
        $this->assertSame('Renamed', $fresh->title);
        $this->assertSame('available', $fresh->status->value);
    }

    public function test_status_endpoint_is_audited(): void
    {
        $this->actingAs($this->agentUser)
            ->patchJson("/api/properties/{$this->property->id}/status", ['status' => 'reserved'])
            ->assertOk();

        $this->assertSame('reserved', $this->property->fresh()->status->value);
        $this->assertDatabaseHas('logs', ['action' => 'property.status_changed', 'agency_id' => $this->agency->id]);
    }

    public function test_is_featured_is_admin_only_on_update(): void
    {
        $this->actingAs($this->agentUser)
            ->putJson("/api/properties/{$this->property->id}", ['is_featured' => true])->assertOk();
        $this->assertFalse($this->property->fresh()->is_featured);

        $this->actingAs($this->admin)
            ->putJson("/api/properties/{$this->property->id}", ['is_featured' => true])->assertOk();
        $this->assertTrue($this->property->fresh()->is_featured);
    }

    public function test_cross_tenant_agent_reference_is_rejected_on_create(): void
    {
        $foreignAgent = $this->makeAgent(Agency::factory()->create())->agent;

        $this->actingAs($this->admin)
            ->postJson('/api/properties', $this->payload(['agent_id' => $foreignAgent->id]))
            ->assertStatus(422)->assertJsonValidationErrors('agent_id');

        $this->postJson('/api/properties', $this->payload(['agent_id' => $this->agentUser->agent->id]))
            ->assertStatus(201)->assertJsonPath('data.agency_id', $this->agency->id);
    }

    public function test_super_admin_creating_a_property_gets_422_not_500(): void
    {
        $res = $this->actingAs($this->makeSuperAdmin())
            ->postJson('/api/properties', $this->payload())
            ->assertStatus(422);

        $this->assertStringContainsString('agency', strtolower($res->json('message')));
        $this->assertSame(1, Property::withoutGlobalScope('agency')->count());
    }

    public function test_staff_cannot_touch_other_agencies_properties(): void
    {
        $other = Property::factory()->create();

        $this->actingAs($this->admin);
        $this->getJson("/api/properties/{$other->id}")->assertStatus(404);
        $this->putJson("/api/properties/{$other->id}", ['title' => 'x'])->assertStatus(404);
        $this->deleteJson("/api/properties/{$other->id}")->assertStatus(404);
    }

    public function test_agent_cannot_edit_a_colleagues_property(): void
    {
        $colleague = $this->makeAgent($this->agency);

        $this->actingAs($colleague)->putJson("/api/properties/{$this->property->id}", ['title' => 'x'])->assertStatus(403);
        $this->deleteJson("/api/properties/{$this->property->id}")->assertStatus(403);
    }

    public function test_clients_only_see_available_listings_of_active_agencies(): void
    {
        $client = $this->makeClientUser();
        $sold = Property::factory()->status('sold')->create(['agency_id' => $this->agency->id]);
        $dormant = Property::factory()->create(['agency_id' => Agency::factory()->inactive()->create()->id]);

        $this->actingAs($client);
        $ids = collect($this->getJson('/api/properties')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertContains($this->property->id, $ids);
        $this->assertNotContains($sold->id, $ids);
        $this->assertNotContains($dormant->id, $ids);

        $this->getJson("/api/properties/{$this->property->id}")->assertOk();
        $this->getJson("/api/properties/{$sold->id}")->assertStatus(403);
        $this->getJson("/api/properties/{$dormant->id}")->assertStatus(403);
        $this->postJson('/api/properties', $this->payload())->assertStatus(403);
    }

    public function test_public_browse_hides_inactive_agencies(): void
    {
        $dormantAgency = Agency::factory()->inactive()->create();
        Property::factory()->create(['agency_id' => $dormantAgency->id, 'title' => 'Dormant Listing']);

        $titles = collect($this->getJson('/api/browse')->assertOk()->json('data'))->pluck('title');

        $this->assertTrue($titles->contains($this->property->title));
        $this->assertFalse($titles->contains('Dormant Listing'));
    }

    public function test_public_browse_per_page_is_fixed_and_safe(): void
    {
        $this->getJson('/api/browse?per_page=0')->assertOk();
    }
}
