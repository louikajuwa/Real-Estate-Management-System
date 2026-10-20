<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgencySeeder extends Seeder
{
    public function run(): void
    {
        // ── Super Admin ──────────────────────────────────────────────────────
        try {
            $superAdmin = User::firstOrCreate(
                ['email' => config('platform.super_admin.email')],
                [
                    'name' => config('platform.super_admin.name'),
                    'password' => Hash::make(config('platform.super_admin.password')),
                    'agency_id' => null,
                    'is_active' => true,
                ]
            );
            $superAdmin->syncRoles([RoleName::SuperAdmin->value]);
            $this->command->info('Super admin seeded: '.$superAdmin->email);
        } catch (\Throwable $e) {
            $this->command->error('Super admin seeding failed: '.$e->getMessage());
        }

        // ── Demo Agency ──────────────────────────────────────────────────────
        try {
            $agency = Agency::firstOrCreate(
                ['slug' => 'sunrise-realty'],
                [
                    'name' => 'Sunrise Realty',
                    'tagline' => 'Find your perfect home with us',
                    'city' => 'Dubai',
                    'country' => 'UAE',
                    'email' => 'info@sunrise-realty.ae',
                    'phone' => '+971 4 123 4567',
                    'website' => 'https://sunrise-realty.ae',
                    'is_active' => true,
                    'plan_type' => 'pro',
                ]
            );
            $this->command->info('Demo agency seeded: '.$agency->name.' (id: '.$agency->id.')');
        } catch (\Throwable $e) {
            $this->command->error('Agency seeding failed: '.$e->getMessage());
        }
    }
}
