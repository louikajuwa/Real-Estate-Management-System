<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * Default: a CRM record without a login (user_id null, no agency yet).
 * Use ->withUser() for a portal client (user with `client` role, no agency).
 *
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'agency_id' => null,
            'user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
        ];
    }

    public function withUser(): static
    {
        return $this->state(fn (array $attrs) => [
            'user_id' => User::factory()->state(['name' => $attrs['name'], 'email' => $attrs['email']]),
        ])->afterCreating(function (Client $client) {
            Role::findOrCreate('client', 'web');
            $client->user?->assignRole('client');
        });
    }
}
