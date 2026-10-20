<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\ChatMessage;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ChatAuthorizationTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    private Agency $a;

    private Agency $b;

    private User $adminA;

    private User $agentA;

    private User $adminB;

    private User $clientOfA;

    private User $clientOfB;

    private User $unrelatedClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Redis::shouldReceive('publish')->zeroOrMoreTimes()->andReturn(1);

        $this->a = Agency::factory()->create();
        $this->b = Agency::factory()->create();
        $this->adminA = $this->makeAdmin($this->a);
        $this->agentA = $this->makeAgent($this->a);
        $this->adminB = $this->makeAdmin($this->b);
        $this->clientOfA = $this->makeClientUser($this->a);
        $this->clientOfB = $this->makeClientUser($this->b);
        $this->unrelatedClient = $this->makeClientUser();
    }

    private function contactIds(User $user): array
    {
        return collect($this->actingAs($user)->getJson('/api/chat/users')->assertOk()->json('data'))
            ->pluck('id')->sort()->values()->all();
    }

    public function test_staff_contacts_are_limited_to_their_agency_and_related_clients(): void
    {
        $ids = $this->contactIds($this->adminA);

        $this->assertContains($this->agentA->id, $ids);
        $this->assertContains($this->clientOfA->id, $ids);
        $this->assertNotContains($this->adminB->id, $ids);
        $this->assertNotContains($this->clientOfB->id, $ids);
        $this->assertNotContains($this->unrelatedClient->id, $ids);
        $this->assertNotContains($this->adminA->id, $ids);
    }

    public function test_agent_does_not_see_all_clients_of_the_platform(): void
    {
        $ids = $this->contactIds($this->agentA);

        $this->assertContains($this->adminA->id, $ids);
        $this->assertNotContains($this->clientOfB->id, $ids);
        $this->assertNotContains($this->unrelatedClient->id, $ids);
    }

    public function test_client_related_through_an_inquiry_becomes_a_contact(): void
    {
        $property = Property::factory()->create(['agency_id' => $this->a->id]);
        Inquiry::factory()->create([
            'property_id' => $property->id,
            'client_id' => $this->unrelatedClient->client->id,
        ]);

        $this->assertContains($this->unrelatedClient->id, $this->contactIds($this->adminA));
        $this->assertContains($this->adminA->id, $this->contactIds($this->unrelatedClient));
    }

    public function test_client_contacts_are_staff_of_related_agencies_only(): void
    {
        $ids = $this->contactIds($this->clientOfA);

        $this->assertContains($this->adminA->id, $ids);
        $this->assertContains($this->agentA->id, $ids);
        $this->assertNotContains($this->adminB->id, $ids);
        $this->assertNotContains($this->clientOfB->id, $ids);
        $this->assertSame([], $this->contactIds($this->unrelatedClient));
    }

    public function test_send_is_restricted_to_allowed_contacts(): void
    {
        $this->actingAs($this->adminA);
        $this->postJson('/api/chat', ['receiver_id' => $this->adminB->id, 'message' => 'hi'])->assertStatus(403);
        $this->postJson('/api/chat', ['receiver_id' => $this->clientOfB->id, 'message' => 'hi'])->assertStatus(403);
        $this->postJson('/api/chat', ['receiver_id' => $this->unrelatedClient->id, 'message' => 'hi'])->assertStatus(403);
        $this->postJson('/api/chat', ['receiver_id' => $this->adminA->id, 'message' => 'hi'])->assertStatus(422);
        $this->assertSame(0, ChatMessage::count());

        $this->postJson('/api/chat', ['receiver_id' => $this->agentA->id, 'message' => 'hi'])->assertStatus(201);
        $this->postJson('/api/chat', ['receiver_id' => $this->clientOfA->id, 'message' => 'hi'])->assertStatus(201);
        $this->assertSame(2, ChatMessage::count());

        // The client can reply to staff of their agency but not message another agency or other clients.
        $this->actingAs($this->clientOfA);
        $this->postJson('/api/chat', ['receiver_id' => $this->adminA->id, 'message' => 'reply'])->assertStatus(201);
        $this->postJson('/api/chat', ['receiver_id' => $this->adminB->id, 'message' => 'hi'])->assertStatus(403);
        $this->postJson('/api/chat', ['receiver_id' => $this->clientOfB->id, 'message' => 'hi'])->assertStatus(403);
    }

    public function test_deactivated_users_cannot_be_messaged(): void
    {
        $this->agentA->update(['is_active' => false]);

        $this->actingAs($this->adminA)
            ->postJson('/api/chat', ['receiver_id' => $this->agentA->id, 'message' => 'hi'])
            ->assertStatus(403);
    }

    public function test_messages_per_page_is_clamped(): void
    {
        $this->actingAs($this->adminA);

        $this->getJson("/api/chat/{$this->agentA->id}/messages?per_page=0")->assertOk();
        $this->getJson("/api/chat/{$this->agentA->id}/messages?per_page=-3")->assertOk();
        $this->getJson("/api/chat/{$this->agentA->id}/messages?per_page=100000")
            ->assertOk()->assertJsonPath('pagination.per_page', 100);
    }

    public function test_super_admin_and_agency_admins_can_reach_each_other(): void
    {
        $super = $this->makeSuperAdmin();

        $this->assertContains($this->adminA->id, $this->contactIds($super));
        $this->assertNotContains($this->clientOfA->id, $this->contactIds($super));
        $this->assertContains($super->id, $this->contactIds($this->adminA));
    }
}
