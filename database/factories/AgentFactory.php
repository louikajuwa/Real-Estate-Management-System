<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * Creates the agent together with its staff user (same agency, `agent` role).
 *
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => fn (array $attrs) => User::factory()->state(['agency_id' => $attrs['agency_id']]),
            'license_no' => 'RE-'.fake()->unique()->numerify('#####'),
            'bio' => fake()->sentence(),
            'phone' => fake()->phoneNumber(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Agent $agent) {
            Role::findOrCreate('agent', 'web');
            $agent->user?->assignRole('agent');
        });
    }
}
