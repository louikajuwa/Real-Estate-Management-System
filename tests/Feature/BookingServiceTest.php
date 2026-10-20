<?php

namespace Tests\Feature;

use App\Exceptions\DoubleBookingException;
use App\Models\Agent;
use App\Models\Client;
use App\Models\Property;
use App\Models\User;
use App\Services\BookingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Property $property;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');

        $agentUser = User::factory()->create();
        $agent = Agent::create(['user_id' => $agentUser->id, 'license_no' => 'RE-TEST']);

        $this->property = Property::create([
            'title' => 'Test Villa',
            'slug' => 'test-villa',
            'description' => null,
            'type' => 'villa',
            'price' => 1000000,
            'address' => '1 Test St',
            'city' => 'Dubai',
            'status' => 'available',
            'is_featured' => false,
            'agent_id' => $agent->id,
        ]);

        $this->client = Client::create(['name' => 'Test Client', 'email' => 'c@example.com']);
    }

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    public function test_non_overlapping_bookings_are_allowed(): void
    {
        $this->service()->schedule([
            'property_id' => $this->property->id,
            'client_id' => $this->client->id,
            'scheduled_at' => '2026-07-01 10:00:00',
            'duration_min' => 60,
        ], $this->admin);

        $second = $this->service()->schedule([
            'property_id' => $this->property->id,
            'client_id' => $this->client->id,
            'scheduled_at' => '2026-07-01 11:00:00', // starts exactly when the first ends
            'duration_min' => 60,
        ], $this->admin);

        $this->assertNotNull($second->id);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_client_booking_is_routed_to_the_listing_agent(): void
    {
        // Regression: client-created bookings used to have agent_id = null and
        // never appeared in the listing agent's scoped list.
        $clientUser = User::factory()->create();
        $clientUser->assignRole('client');
        $client = Client::create(['user_id' => $clientUser->id, 'name' => 'Buyer', 'email' => 'b@example.com']);

        $booking = $this->service()->schedule([
            'property_id' => $this->property->id,
            'scheduled_at' => '2026-08-01 14:00:00',
            'duration_min' => 30,
        ], $clientUser);

        $this->assertSame($this->property->agent_id, $booking->agent_id,
            'Client booking should inherit the property listing agent');
    }

    public function test_overlapping_booking_throws_double_booking(): void
    {
        $this->service()->schedule([
            'property_id' => $this->property->id,
            'client_id' => $this->client->id,
            'scheduled_at' => '2026-07-01 10:00:00',
            'duration_min' => 60,
        ], $this->admin);

        $this->expectException(DoubleBookingException::class);

        $this->service()->schedule([
            'property_id' => $this->property->id,
            'client_id' => $this->client->id,
            'scheduled_at' => '2026-07-01 10:30:00', // overlaps 10:00-11:00
            'duration_min' => 60,
        ], $this->admin);
    }
}
