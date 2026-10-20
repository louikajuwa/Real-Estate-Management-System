<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guest_root_redirects_to_browse(): void
    {
        // Guests hitting the root are redirected to the public browse page.
        $response = $this->get('/');

        $response->assertRedirect(route('browse'));
    }

    public function test_browse_page_loads_for_guests(): void
    {
        $response = $this->get('/browse');

        $response->assertStatus(200);
    }
}
