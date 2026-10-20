<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ClientIdorTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    private Agency $agency;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $this->agency = Agency::factory()->create();
        $this->property = Property::factory()->create(['agency_id' => $this->agency->id]);
    }

    private function futureSlot(int $days = 5): string
    {
        return now()->addDays($days)->setTime(10, 0)->toDateTimeString();
    }

    public function test_client_inquiry_ignores_supplied_client_id(): void
    {
        $me = $this->makeClientUser();
        $victim = Client::factory()->create(['agency_id' => null]);

        $this->actingAs($me)->postJson('/api/inquiries', [
            'property_id' => $this->property->id,
            'client_id' => $victim->id,
            'message' => 'Hello',
        ])->assertStatus(201);

        $inquiry = Inquiry::firstOrFail();
        $this->assertSame($me->client->id, $inquiry->client_id);
        // The victim must not have been claimed into the agency.
        $this->assertNull($victim->fresh()->agency_id);
    }

    public function test_client_booking_ignores_supplied_client_id(): void
    {
        $me = $this->makeClientUser();
        $victim = Client::factory()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($me)->postJson('/api/bookings', [
            'property_id' => $this->property->id,
            'client_id' => $victim->id,
            'scheduled_at' => $this->futureSlot(),
        ])->assertStatus(201);

        $this->assertSame($me->client->id, Booking::firstOrFail()->client_id);
    }

    public function test_own_client_record_is_claimed_by_the_agency_on_first_engagement(): void
    {
        $me = $this->makeClientUser();

        $this->actingAs($me)->postJson('/api/inquiries', [
            'property_id' => $this->property->id, 'message' => 'Hi',
        ])->assertStatus(201);

        $this->assertSame($this->agency->id, $me->client->fresh()->agency_id);
    }

    public function test_client_without_crm_record_gets_one_instead_of_a_500(): void
    {
        $me = $this->makeClientUser();
        $me->client->delete();

        $this->actingAs($me->fresh())->postJson('/api/inquiries', [
            'property_id' => $this->property->id, 'message' => 'Hi',
        ])->assertStatus(201);

        $this->assertDatabaseHas('clients', ['user_id' => $me->id]);
    }

    public function test_staff_must_supply_a_client_id_from_their_own_agency(): void
    {
        $admin = $this->makeAdmin($this->agency);
        $foreign = Client::factory()->create(['agency_id' => Agency::factory()->create()->id]);
        $unclaimed = Client::factory()->create(['agency_id' => null]);

        $this->actingAs($admin);
        $this->postJson('/api/inquiries', ['property_id' => $this->property->id, 'message' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('client_id');
        $this->postJson('/api/inquiries', ['property_id' => $this->property->id, 'client_id' => $foreign->id, 'message' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('client_id');
        $this->postJson('/api/inquiries', ['property_id' => $this->property->id, 'client_id' => $unclaimed->id, 'message' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('client_id');
        $this->postJson('/api/bookings', [
            'property_id' => $this->property->id, 'client_id' => $foreign->id, 'scheduled_at' => $this->futureSlot(),
        ])->assertStatus(422)->assertJsonValidationErrors('client_id');

        $own = Client::factory()->create(['agency_id' => $this->agency->id]);
        $this->postJson('/api/inquiries', ['property_id' => $this->property->id, 'client_id' => $own->id, 'message' => 'x'])
            ->assertStatus(201);
    }

    public function test_cross_tenant_property_reference_is_rejected_for_staff(): void
    {
        $admin = $this->makeAdmin($this->agency);
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        $foreignProperty = Property::factory()->create();

        $this->actingAs($admin);
        $this->postJson('/api/inquiries', ['property_id' => $foreignProperty->id, 'client_id' => $client->id, 'message' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('property_id');
        $this->postJson('/api/bookings', [
            'property_id' => $foreignProperty->id, 'client_id' => $client->id, 'scheduled_at' => $this->futureSlot(),
        ])->assertStatus(422)->assertJsonValidationErrors('property_id');
    }

    public function test_clients_can_only_engage_available_properties_of_active_agencies(): void
    {
        $me = $this->makeClientUser();
        $sold = Property::factory()->status('sold')->create(['agency_id' => $this->agency->id]);
        $dormant = Property::factory()->create(['agency_id' => Agency::factory()->inactive()->create()->id]);
        $trashed = Property::factory()->create(['agency_id' => $this->agency->id]);
        $trashed->delete();

        $this->actingAs($me);
        foreach ([$sold, $dormant, $trashed] as $property) {
            $this->postJson('/api/inquiries', ['property_id' => $property->id, 'message' => 'x'])
                ->assertStatus(422)->assertJsonValidationErrors('property_id');
        }
        $this->postJson('/api/bookings', ['property_id' => $dormant->id, 'scheduled_at' => $this->futureSlot()])
            ->assertStatus(422);
    }

    public function test_super_admin_cannot_create_inquiries_or_bookings(): void
    {
        $super = $this->makeSuperAdmin();

        $this->actingAs($super)->postJson('/api/inquiries', ['property_id' => $this->property->id, 'message' => 'x'])
            ->assertStatus(403);
        $this->postJson('/api/bookings', ['property_id' => $this->property->id, 'scheduled_at' => $this->futureSlot()])
            ->assertStatus(403);
    }
}
