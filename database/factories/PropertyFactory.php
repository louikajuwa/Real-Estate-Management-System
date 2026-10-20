<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->streetName().' Residence';

        return [
            'agency_id' => Agency::factory(),
            'agent_id' => fn (array $attrs) => Agent::factory()->state(['agency_id' => $attrs['agency_id']]),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'description' => fake()->paragraph(),
            'type' => 'apartment',
            'price' => fake()->numberBetween(100000, 5000000),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'bedrooms' => 2,
            'bathrooms' => 2,
            'area_sqft' => 1200,
            'status' => 'available',
            'is_featured' => false,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
