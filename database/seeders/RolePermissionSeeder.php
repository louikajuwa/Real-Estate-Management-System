<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Enums\RoleName;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = [
            'properties.view',
            'properties.create',
            'properties.edit',
            'properties.delete',
            'agents.view',
            'agents.create',
            'agents.edit',
            'agents.delete',
            'clients.view',
            'clients.create',
            'clients.edit',
            'clients.delete',
            'inquiries.view',
            'inquiries.create',
            'inquiries.edit',
            'inquiries.delete',
            'bookings.view',
            'bookings.create',
            'bookings.edit',
            'bookings.delete',
            'logs.view',
            'roles.manage',
            'dashboard.view',
            'chat.access',
            'notifications.access',
            // Super admin platform permissions
            'agencies.view',
            'agencies.create',
            'agencies.edit',
            'agencies.delete',
            'super_admin.dashboard',
        ];

        foreach ($allPermissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // Super Admin — platform owner, all permissions
        $superAdminRole = Role::firstOrCreate(['name' => RoleName::SuperAdmin->value, 'guard_name' => 'web']);
        $superAdminRole->syncPermissions($allPermissions);

        // Admin — agency administrator, all agency permissions
        $adminRole = Role::firstOrCreate(['name' => RoleName::Admin->value, 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'properties.view', 'properties.create', 'properties.edit', 'properties.delete',
            'agents.view', 'agents.create', 'agents.edit', 'agents.delete',
            'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
            'inquiries.view', 'inquiries.create', 'inquiries.edit', 'inquiries.delete',
            'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.delete',
            'logs.view', 'roles.manage', 'dashboard.view', 'chat.access', 'notifications.access',
        ]);

        // Agent — agency broker
        $agentRole = Role::firstOrCreate(['name' => RoleName::Agent->value, 'guard_name' => 'web']);
        $agentRole->syncPermissions([
            'properties.view', 'properties.create', 'properties.edit',
            'clients.view', 'clients.create', 'clients.edit',
            'inquiries.view', 'inquiries.create', 'inquiries.edit',
            'bookings.view', 'bookings.create', 'bookings.edit',
            'dashboard.view', 'chat.access', 'notifications.access',
        ]);

        // Client — platform customer (platform-wide, not agency-scoped)
        $clientRole = Role::firstOrCreate(['name' => RoleName::Client->value, 'guard_name' => 'web']);
        $clientRole->syncPermissions([
            'properties.view',
            'inquiries.view', 'inquiries.create',
            'bookings.view', 'bookings.create',
            'dashboard.view', 'chat.access', 'notifications.access',
        ]);

        $this->command->info('Roles and permissions seeded (including super_admin).');
    }
}
