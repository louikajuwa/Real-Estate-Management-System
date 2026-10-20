<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class BookingRulesTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private Property $property;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $this->agency = Agency::factory()->create();
        $this->admin = $this->makeAdmin($this->agency);
        $this->property = Property::factory()->create(['agency_id' => $this->agency->id]);
        $this->client = Client::factory()->create(['agency_id' => $this->agency->id]);
    }

    private function slot(int $days = 5, int $hour = 10): string
    {
        return now()->addDays($days)->setTime($hour, 0)->toDateTimeString();
    }

    private function book(string $at, int $duration = 60)
    {
        return $this->actingAs($this->admin)->postJson('/api/bookings', [
            'property_id' => $this->property->id,
            'client_id' => $this->client->id,
            'scheduled_at' => $at,
            'duration_min' => $duration,
        ]);
    }

    private function setStatus(Booking $booking, string $status, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->admin)
            ->patchJson("/api/bookings/{$booking->id}/status", ['status' => $status]);
    }

    public function test_cancelled_slot_can_be_rebooked(): void
    {
        $first = $this->book($this->slot())->assertStatus(201);
        $booking = Booking::findOrFail($first->json('data.id'));

        $this->setStatus($booking, 'cancelled')->assertOk();

        // Previously a DB unique(property_id, scheduled_at) violation -> 500.
        $this->book($this->slot())->assertStatus(201);
        $this->assertSame(2, Booking::count());
    }

    public function test_overlapping_booking_is_rejected_with_422(): void
    {
        $this->book($this->slot())->assertStatus(201);
        $this->book(now()->addDays(5)->setTime(10, 30)->toDateTimeString())->assertStatus(422);
    }

    public function test_invalid_status_transitions_are_rejected(): void
    {
        $completed = Booking::factory()->status('completed')->create(['property_id' => $this->property->id, 'client_id' => $this->client->id]);
        $scheduled = Booking::factory()->create(['property_id' => $this->property->id, 'client_id' => $this->client->id]);

        $this->setStatus($completed, 'scheduled')->assertStatus(422);
        $this->setStatus($completed, 'cancelled')->assertStatus(422);
        $this->setStatus($scheduled, 'scheduled')->assertStatus(422);
        $this->setStatus($scheduled, 'bogus')->assertStatus(422);

        $this->assertSame('completed', $completed->fresh()->status->value);

        $this->setStatus($scheduled, 'completed')->assertOk();
    }

    public function test_reopening_a_cancelled_booking_rechecks_overlap(): void
    {
        $at = now()->addDays(7)->setTime(10, 0);
        $cancelled = Booking::factory()->status('cancelled')->create([
            'property_id' => $this->property->id, 'client_id' => $this->client->id, 'scheduled_at' => $at,
        ]);
        Booking::factory()->create([
            'property_id' => $this->property->id, 'client_id' => $this->client->id,
            'scheduled_at' => $at->copy()->addMinutes(30),
        ]);

        $this->setStatus($cancelled, 'scheduled')->assertStatus(422);
        $this->assertSame('cancelled', $cancelled->fresh()->status->value);

        // Free slot: re-opening works.
        $free = Booking::factory()->status('cancelled')->create([
            'property_id' => $this->property->id, 'client_id' => $this->client->id,
            'scheduled_at' => now()->addDays(9)->setTime(15, 0),
        ]);
        $this->setStatus($free, 'scheduled')->assertOk();
    }

    public function test_client_may_only_cancel_their_own_booking(): void
    {
        $me = $this->makeClientUser($this->agency);
        $mine = Booking::factory()->create(['property_id' => $this->property->id, 'client_id' => $me->client->id]);
        $theirs = Booking::factory()->create(['property_id' => $this->property->id, 'client_id' => $this->client->id, 'scheduled_at' => $this->slot(8)]);

        $this->setStatus($mine, 'completed', $me)->assertStatus(403);
        $this->setStatus($theirs, 'cancelled', $me)->assertStatus(403);
        $this->setStatus($mine, 'cancelled', $me)->assertOk();
    }

    public function test_unique_violation_race_is_reported_as_422_not_500(): void
    {
        // Simulate a legacy/concurrent unique(property_id, scheduled_at): a cancelled row
        // holds the slot, the overlap check passes, and the INSERT hits the constraint.
        $at = now()->addDays(10)->setTime(11, 0);
        Booking::factory()->status('cancelled')->create([
            'property_id' => $this->property->id, 'client_id' => $this->client->id, 'scheduled_at' => $at,
        ]);
        Schema::table('bookings', fn ($t) => $t->unique(['property_id', 'scheduled_at'], 'race_unique'));

        $this->book($at->toDateTimeString())->assertStatus(422);
        $this->assertSame(1, Booking::count());
    }

    public function test_migration_dropped_the_slot_unique_index(): void
    {
        $at = now()->addDays(12)->setTime(9, 0);
        Booking::factory()->status('cancelled')->create([
            'property_id' => $this->property->id, 'client_id' => $this->client->id, 'scheduled_at' => $at,
        ]);

        // Would throw if unique(property_id, scheduled_at) still existed.
        Booking::factory()->create([
            'property_id' => $this->property->id, 'client_id' => $this->client->id, 'scheduled_at' => $at,
        ]);
        $this->assertSame(2, Booking::count());
    }
}
