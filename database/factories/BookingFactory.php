<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * agency_id and agent_id are derived from the property, like BookingService does.
 *
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'agency_id' => fn (array $attrs) => Property::withoutGlobalScope('agency')->find($attrs['property_id'])->agency_id,
            'agent_id' => fn (array $attrs) => Property::withoutGlobalScope('agency')->find($attrs['property_id'])->agent_id,
            'client_id' => fn (array $attrs) => Client::factory()->state(['agency_id' => $attrs['agency_id']]),
            'scheduled_at' => now()->addDays(fake()->numberBetween(2, 60))->setTime(10, 0),
            'duration_min' => 60,
            'status' => 'scheduled',
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
