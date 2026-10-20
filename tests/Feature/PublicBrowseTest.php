<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBrowseTest extends TestCase
{
    use RefreshDatabase;

    private function makeProperty(array $overrides = []): Property
    {
        // Public listings must belong to an active agency.
        $agency = Agency::factory()->create();
        $agent = Agent::create(['agency_id' => $agency->id, 'user_id' => User::factory()->create()->id, 'license_no' => 'RE-X']);

        return Property::create(array_merge([
            'agency_id' => $agency->id,
            'title' => 'Available Home',
            'slug' => 'available-home-'.uniqid(),
            'description' => null,
            'type' => 'apartment',
            'price' => 500000,
            'address' => '1 St',
            'city' => 'Dubai',
            'status' => 'available',
            'is_featured' => false,
            'agent_id' => $agent->id,
        ], $overrides));
    }

    public function test_browse_api_returns_only_available_properties(): void
    {
        $this->makeProperty(['title' => 'Visible', 'status' => 'available']);
        $this->makeProperty(['title' => 'Hidden Sold', 'status' => 'sold']);

        $response = $this->getJson('/api/browse');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Visible'));
        $this->assertFalse($titles->contains('Hidden Sold'), 'Sold properties must not appear on public browse');
    }

    public function test_browse_api_search_filters_by_title(): void
    {
        $this->makeProperty(['title' => 'Marina Penthouse', 'slug' => 'marina-ph']);
        $this->makeProperty(['title' => 'Desert Plot', 'slug' => 'desert-plot']);

        $response = $this->getJson('/api/browse?q=Marina');
        $titles = collect($response->json('data'))->pluck('title');

        $this->assertTrue($titles->contains('Marina Penthouse'));
        $this->assertFalse($titles->contains('Desert Plot'));
    }
}
