<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * agency_id and agent_id are derived from the property, like InquiryService does.
 *
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'agency_id' => fn (array $attrs) => Property::withoutGlobalScope('agency')->find($attrs['property_id'])->agency_id,
            'agent_id' => fn (array $attrs) => Property::withoutGlobalScope('agency')->find($attrs['property_id'])->agent_id,
            'client_id' => fn (array $attrs) => Client::factory()->state(['agency_id' => $attrs['agency_id']]),
            'message' => fake()->sentence(),
            'status' => 'new',
        ];
    }
}
