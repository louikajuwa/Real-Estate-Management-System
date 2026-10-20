<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class PaginationGuardTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->admin = $this->makeAdmin(Agency::factory()->create());
    }

    public static function badPerPageValues(): array
    {
        return [['0'], ['-5'], ['abc'], ['999999'], ['']];
    }

    #[DataProvider('badPerPageValues')]
    public function test_staff_listings_survive_bad_per_page(string $value): void
    {
        $this->actingAs($this->admin);

        foreach (['properties', 'agents', 'clients', 'inquiries', 'bookings', 'logs'] as $endpoint) {
            $res = $this->getJson("/api/{$endpoint}?per_page={$value}");
            $res->assertOk();
            $perPage = $res->json('pagination.per_page');
            $this->assertGreaterThanOrEqual(1, $perPage, "{$endpoint} per_page={$value}");
            $this->assertLessThanOrEqual(200, $perPage, "{$endpoint} per_page={$value}");
        }
    }

    #[DataProvider('badPerPageValues')]
    public function test_super_admin_agency_list_survives_bad_per_page(string $value): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->getJson("/api/super/agencies?per_page={$value}")->assertOk();
    }

    public function test_client_property_listing_survives_zero_per_page(): void
    {
        $this->actingAs($this->makeClientUser())->getJson('/api/properties?per_page=0')->assertOk();
    }
}
