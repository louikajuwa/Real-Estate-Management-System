<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientInteraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientInteraction>
 */
class ClientInteractionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'agent_id' => null,
            'type' => 'call',
            'notes' => fake()->sentence(),
            'occurred_at' => now()->subDay(),
        ];
    }
}
