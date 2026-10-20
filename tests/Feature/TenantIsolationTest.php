<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Client;
use App\Models\ClientInteraction;
use App\Models\Inquiry;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    private Agency $a;

    private Agency $b;

    private User $adminA;

    private User $adminB;

    private User $agentA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $this->a = Agency::factory()->create();
        $this->b = Agency::factory()->create();
        $this->adminA = $this->makeAdmin($this->a);
        $this->adminB = $this->makeAdmin($this->b);
        $this->agentA = $this->makeAgent($this->a);
    }

    public function test_global_scope_applies_for_authenticated_agency_user_outside_console(): void
    {
        Property::factory()->create(['agency_id' => $this->a->id]);
        Property::factory()->create(['agency_id' => $this->b->id]);

        // No auth user (console/seeder behaviour): unscoped.
        $this->assertSame(2, Property::count());

        $this->actingAs($this->adminA);
        $this->assertSame(1, Property::count());
    }

    public function test_staff_cannot_read_other_tenants_records(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->b->id]);
        $inquiry = Inquiry::factory()->create(['agency_id' => $this->b->id]);
        $booking = Booking::factory()->create();
        $bAgent = Agent::factory()->create(['agency_id' => $this->b->id]);

        $this->actingAs($this->adminA);
        $this->getJson("/api/clients/{$client->id}")->assertStatus(404);
        $this->getJson("/api/inquiries/{$inquiry->id}")->assertStatus(404);
        $this->getJson("/api/bookings/{$booking->id}")->assertStatus(404);
        $this->getJson("/api/agents/{$bAgent->id}")->assertStatus(404);
        $this->postJson("/api/clients/{$client->id}/interactions", [
            'type' => 'call', 'notes' => 'x', 'occurred_at' => now()->toDateTimeString(),
        ])->assertStatus(404);
    }

    public function test_staff_lists_only_their_agency(): void
    {
        $mine = Client::factory()->create(['agency_id' => $this->a->id]);
        Client::factory()->create(['agency_id' => $this->b->id]);

        $res = $this->actingAs($this->adminA)->getJson('/api/clients')->assertOk();
        $this->assertSame([$mine->id], collect($res->json('data'))->pluck('id')->all());

        $agents = $this->getJson('/api/agents')->assertOk();
        $this->assertEmpty(collect($agents->json('data'))->where('agency_id', $this->b->id));
    }

    public function test_client_role_cannot_list_or_view_clients(): void
    {
        $clientUser = $this->makeClientUser($this->a);
        $other = Client::factory()->create(['agency_id' => $this->b->id]);

        $this->actingAs($clientUser);
        $this->getJson('/api/clients')->assertStatus(403);
        $this->getJson("/api/clients/{$other->id}")->assertStatus(403);
        $this->getJson("/api/clients/{$other->id}/interactions")->assertStatus(403);
    }

    public function test_client_role_cannot_list_or_view_agents(): void
    {
        $clientUser = $this->makeClientUser($this->a);
        $agent = $this->agentA->agent;

        $this->actingAs($clientUser);
        $this->getJson('/api/agents')->assertStatus(403);
        $this->getJson("/api/agents/{$agent->id}")->assertStatus(403);
    }

    public function test_client_role_cannot_add_interaction_to_any_client(): void
    {
        $clientUser = $this->makeClientUser($this->a);
        $victim = Client::factory()->create(['agency_id' => $this->b->id]);

        $this->actingAs($clientUser)->postJson("/api/clients/{$victim->id}/interactions", [
            'type' => 'note', 'notes' => 'spam', 'occurred_at' => now()->toDateTimeString(),
        ])->assertStatus(403);

        $this->assertSame(0, ClientInteraction::count());
    }

    public function test_client_can_only_view_own_inquiries_and_bookings(): void
    {
        $me = $this->makeClientUser($this->a);
        $other = $this->makeClientUser($this->a);

        $mine = Inquiry::factory()->create(['agency_id' => $this->a->id, 'client_id' => $me->client->id]);
        $theirs = Inquiry::factory()->create(['agency_id' => $this->a->id, 'client_id' => $other->client->id]);
        $theirBooking = Booking::factory()->create(['agency_id' => $this->a->id, 'client_id' => $other->client->id]);

        $this->actingAs($me);
        $this->getJson("/api/inquiries/{$mine->id}")->assertOk();
        $this->getJson("/api/inquiries/{$theirs->id}")->assertStatus(403);
        $this->getJson("/api/bookings/{$theirBooking->id}")->assertStatus(403);
        $this->putJson("/api/inquiries/{$mine->id}", ['message' => 'edit'])->assertStatus(403);
        $this->deleteJson("/api/inquiries/{$mine->id}")->assertStatus(403);
        $this->patchJson("/api/inquiries/{$mine->id}/status", ['status' => 'closed'])->assertStatus(403);
    }

    public function test_agent_is_limited_to_assigned_inquiries_bookings_and_clients(): void
    {
        $colleague = $this->makeAgent($this->a);
        $property = Property::factory()->create(['agency_id' => $this->a->id, 'agent_id' => $colleague->agent->id]);
        $inquiry = Inquiry::factory()->create(['property_id' => $property->id]);
        $booking = Booking::factory()->create(['property_id' => $property->id]);
        $client = Client::factory()->create(['agency_id' => $this->a->id, 'assigned_agent_id' => $colleague->agent->id]);

        $this->actingAs($this->agentA);
        $this->getJson("/api/inquiries/{$inquiry->id}")->assertStatus(403);
        $this->getJson("/api/bookings/{$booking->id}")->assertStatus(403);
        $this->putJson("/api/bookings/{$booking->id}", ['notes' => 'x'])->assertStatus(403);
        $this->getJson("/api/clients/{$client->id}")->assertStatus(403);
        $this->putJson("/api/clients/{$client->id}", ['name' => 'Hijack'])->assertStatus(403);
        $this->deleteJson("/api/clients/{$client->id}")->assertStatus(403);
        $this->deleteJson("/api/agents/{$colleague->agent->id}")->assertStatus(403);

        // Admin of the same agency may see all of them.
        $this->actingAs($this->adminA);
        $this->getJson("/api/inquiries/{$inquiry->id}")->assertOk();
        $this->getJson("/api/bookings/{$booking->id}")->assertOk();
        $this->getJson("/api/clients/{$client->id}")->assertOk();
    }

    public function test_agent_can_manage_assigned_records(): void
    {
        $property = Property::factory()->create(['agency_id' => $this->a->id, 'agent_id' => $this->agentA->agent->id]);
        $inquiry = Inquiry::factory()->create(['property_id' => $property->id]);

        $this->actingAs($this->agentA);
        $this->getJson("/api/inquiries/{$inquiry->id}")->assertOk();
        $this->patchJson("/api/inquiries/{$inquiry->id}/status", ['status' => 'contacted'])->assertOk();
    }

    public function test_only_admin_of_same_agency_can_delete_inquiries_and_agents(): void
    {
        $inquiry = Inquiry::factory()->create(['agency_id' => $this->a->id]);

        $this->actingAs($this->adminB)->deleteJson("/api/inquiries/{$inquiry->id}")->assertStatus(404);
        $this->actingAs($this->adminA)->deleteJson("/api/inquiries/{$inquiry->id}")->assertOk();
    }

    public function test_interaction_save_returns_201_without_agent_id(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->a->id]);
        $payload = ['type' => 'call', 'notes' => 'Called back', 'occurred_at' => now()->toDateTimeString()];

        // Admin, no agent_id supplied (previously a 500: undefined array key).
        $this->actingAs($this->adminA)->postJson("/api/clients/{$client->id}/interactions", $payload)
            ->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('client_interactions', ['client_id' => $client->id, 'agent_id' => null]);
    }

    public function test_agent_interactions_are_attributed_to_the_agent_and_cannot_be_spoofed(): void
    {
        $other = $this->makeAgent($this->a);
        $client = Client::factory()->create(['agency_id' => $this->a->id, 'assigned_agent_id' => $this->agentA->agent->id]);

        $this->actingAs($this->agentA)->postJson("/api/clients/{$client->id}/interactions", [
            'type' => 'meeting', 'notes' => 'Met', 'occurred_at' => now()->toDateTimeString(),
            'agent_id' => $other->agent->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('client_interactions', [
            'client_id' => $client->id, 'agent_id' => $this->agentA->agent->id,
        ]);
    }

    public function test_interaction_agent_must_belong_to_same_agency(): void
    {
        $bAgent = Agent::factory()->create(['agency_id' => $this->b->id]);
        $client = Client::factory()->create(['agency_id' => $this->a->id]);

        $this->actingAs($this->adminA)->postJson("/api/clients/{$client->id}/interactions", [
            'type' => 'call', 'notes' => 'x', 'occurred_at' => now()->toDateTimeString(),
            'agent_id' => $bAgent->id,
        ])->assertStatus(422)->assertJsonValidationErrors('agent_id');
    }

    public function test_cross_tenant_agent_reference_is_rejected_when_creating_or_updating_clients(): void
    {
        $bAgent = Agent::factory()->create(['agency_id' => $this->b->id]);

        $this->actingAs($this->adminA)->postJson('/api/clients', [
            'name' => 'New', 'assigned_agent_id' => $bAgent->id,
        ])->assertStatus(422)->assertJsonValidationErrors('assigned_agent_id');

        $client = Client::factory()->create(['agency_id' => $this->a->id]);
        $this->putJson("/api/clients/{$client->id}", ['assigned_agent_id' => $bAgent->id])
            ->assertStatus(422);
    }

    public function test_agent_listing_is_not_polluted_by_search_filter(): void
    {
        // The q filter mixes OR conditions; it must not escape the tenant scope.
        Agent::factory()->create(['agency_id' => $this->b->id, 'license_no' => 'FINDME-1']);
        $mine = Agent::factory()->create(['agency_id' => $this->a->id, 'license_no' => 'FINDME-2']);

        $res = $this->actingAs($this->adminA)->getJson('/api/agents?q=FINDME')->assertOk();
        $this->assertSame([$mine->id], collect($res->json('data'))->pluck('id')->all());
    }

    public function test_client_originated_audit_logs_inherit_the_subject_agency(): void
    {
        $clientUser = $this->makeClientUser();
        $property = Property::factory()->create(['agency_id' => $this->a->id]);

        $this->actingAs($clientUser)->postJson('/api/inquiries', [
            'property_id' => $property->id, 'message' => 'Interested',
        ])->assertStatus(201);

        $log = AuditLog::where('action', 'inquiry.submitted')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->a->id, $log->agency_id);
    }
}
