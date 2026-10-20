<?php

namespace Tests\Concerns;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

trait CreatesTenants
{
    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function makeAdmin(?Agency $agency = null): User
    {
        $agency ??= Agency::factory()->create();
        $user = User::factory()->create(['agency_id' => $agency->id, 'is_active' => true]);
        $user->assignRole('admin');

        return $user;
    }

    protected function makeAgent(?Agency $agency = null): User
    {
        $agency ??= Agency::factory()->create();
        $agent = Agent::factory()->create(['agency_id' => $agency->id]);

        return $agent->user;
    }

    protected function makeSuperAdmin(): User
    {
        $user = User::factory()->create(['agency_id' => null, 'is_active' => true]);
        $user->assignRole('super_admin');

        return $user;
    }

    /** A portal client: user (client role, no agency) with a Client record. */
    protected function makeClientUser(?Agency $agency = null, array $clientAttrs = []): User
    {
        $client = Client::factory()->withUser()->create(array_merge(['agency_id' => $agency?->id], $clientAttrs));

        return $client->user;
    }
}
