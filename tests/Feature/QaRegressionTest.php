<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\Client;
use App\Models\Property;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/** Regressions found during browser QA (2026-10-15). */
class QaRegressionTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_web_pages_are_gated_by_role(): void
    {
        $agency = Agency::factory()->create();
        $admin = $this->makeAdmin($agency);
        $agent = $this->makeAgent($agency);
        $client = $this->makeClientUser($agency);

        foreach (['/agents', '/clients', '/logs'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
            $this->actingAs($client)->get($url)->assertForbidden();
        }

        $this->actingAs($agent)->get('/agents')->assertOk();
        $this->actingAs($agent)->get('/clients')->assertOk();
        $this->actingAs($agent)->get('/logs')->assertForbidden();
        $this->actingAs($client)->get('/bookings')->assertOk();
    }

    public function test_browse_rejects_array_params_with_422(): void
    {
        $this->getJson('/api/browse?q[]=x')->assertStatus(422);
        $this->getJson('/api/browse?city[]=x')->assertStatus(422);
        $this->getJson('/api/browse?min_price=abc')->assertStatus(422);
        $this->getJson('/api/browse?q=villa')->assertOk();
    }

    public function test_audit_logs_and_chat_messages_get_created_at(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $log = app(AuditLogger::class)->log('test.action', null, null, null, 'QA');
        $this->assertNotNull($log->fresh()->created_at);

        $msg = ChatMessage::create(['sender_id' => $admin->id, 'receiver_id' => $admin->id, 'message' => 'hi']);
        $this->assertNotNull($msg->fresh()->created_at);
        $this->assertNotNull(AuditLog::first()->created_at);
    }

    public function test_interaction_without_occurred_at_defaults_to_now(): void
    {
        $agency = Agency::factory()->create();
        $admin = $this->makeAdmin($agency);
        $client = Client::factory()->create(['agency_id' => $agency->id]);

        $this->actingAs($admin)
            ->postJson("/api/clients/{$client->id}/interactions", ['type' => 'call', 'notes' => 'Called'])
            ->assertCreated();

        $this->assertNotNull($client->interactions()->first()->occurred_at);
    }

    public function test_utc_iso_booking_time_is_stored_as_utc(): void
    {
        $agency = Agency::factory()->create();
        $admin = $this->makeAdmin($agency);
        $agent = $this->makeAgent($agency);
        $client = Client::factory()->create(['agency_id' => $agency->id]);
        $property = Property::factory()->create([
            'agency_id' => $agency->id, 'agent_id' => $agent->agent->id,
        ]);

        $this->actingAs($admin)->postJson('/api/bookings', [
            'property_id' => $property->id,
            'client_id' => $client->id,
            'scheduled_at' => '2030-03-10T02:00:00.000Z',
        ])->assertCreated();

        $this->assertSame('2030-03-10 02:00:00', $property->bookings()->first()->scheduled_at->format('Y-m-d H:i:s'));
    }
}
