<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class RolesPermissionsPopulator {
    public static function populate(): void {
        $tableNames = config('permission.table_names');

        // Check if roles and permissions already exist - if so, skip population
        $existingRolesCount = DB::connection('tenant')
            ->table($tableNames['roles'])
            ->whereIn('name', ['owner', 'admin', 'editor', 'reader', 'field-agent'])
            ->count();

        if ($existingRolesCount >= 5) {
            // All roles already exist, skip population
            return;
        }

        // Base permission set
        $permissions = [
            // settings
            ['name' => 'settings.view', 'guard_name' => 'api'],
            ['name' => 'settings.update', 'guard_name' => 'api'],
            // role management
            ['name' => 'roles.manage', 'guard_name' => 'api'],
            // horizon
            ['name' => 'horizon.view', 'guard_name' => 'api'],
            // customers
            ['name' => 'customers.view', 'guard_name' => 'api'],
            ['name' => 'customers.create', 'guard_name' => 'api'],
            ['name' => 'customers.update', 'guard_name' => 'api'],
            ['name' => 'customers.delete', 'guard_name' => 'api'],
            // assets
            ['name' => 'assets.view', 'guard_name' => 'api'],
            ['name' => 'assets.create', 'guard_name' => 'api'],
            ['name' => 'assets.update', 'guard_name' => 'api'],
            ['name' => 'assets.delete', 'guard_name' => 'api'],
            // tickets
            ['name' => 'tickets.view', 'guard_name' => 'api'],
            ['name' => 'tickets.create', 'guard_name' => 'api'],
            ['name' => 'tickets.update', 'guard_name' => 'api'],
            ['name' => 'tickets.delete', 'guard_name' => 'api'],
            ['name' => 'tickets.export', 'guard_name' => 'api'],
            // payments/transactions
            ['name' => 'payments.view', 'guard_name' => 'api'],
            ['name' => 'payments.refund', 'guard_name' => 'api'],
            // reports & exports
            ['name' => 'reports.view', 'guard_name' => 'api'],
            ['name' => 'exports.transactions', 'guard_name' => 'api'],
            ['name' => 'exports.customers', 'guard_name' => 'api'],
            ['name' => 'exports.debts', 'guard_name' => 'api'],
            // plugins
            ['name' => 'plugins.manage', 'guard_name' => 'api'],
            // pages
            ['name' => 'page.view:/settings', 'guard_name' => 'api'],
            // agent permissions
            ['name' => 'customers.view', 'guard_name' => 'agent'],
            ['name' => 'customers.create', 'guard_name' => 'agent'],
            ['name' => 'customers.update', 'guard_name' => 'agent'],
            ['name' => 'tickets.view', 'guard_name' => 'agent'],
            ['name' => 'tickets.create', 'guard_name' => 'agent'],
            ['name' => 'tickets.update', 'guard_name' => 'agent'],
            ['name' => 'payments.view', 'guard_name' => 'agent'],
        ];

        foreach ($permissions as $perm) {
            DB::connection('tenant')->table($tableNames['permissions'])->insertOrIgnore([
                'name' => $perm['name'],
                'guard_name' => $perm['guard_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Built-in roles: owner, admin, editor, reader (api guard), field-agent (agent guard)
        $roles = [
            ['name' => 'owner', 'guard_name' => 'api', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin', 'guard_name' => 'api', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editor', 'guard_name' => 'api', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reader', 'guard_name' => 'api', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'field-agent', 'guard_name' => 'agent', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($roles as $role) {
            DB::connection('tenant')->table($tableNames['roles'])->insertOrIgnore($role);
        }

        // Define admin-only permissions (require special privileges)
        $adminOnlyPermissions = [
            'roles.manage',
            'horizon.view',
            'plugins.manage',
            'settings.update',
            'settings.view',
            'exports.transactions',
            'exports.customers',
            'exports.debts',
            'payments.refund',
        ];

        // Get all API permissions
        $allApiPermissions = DB::connection('tenant')
            ->table($tableNames['permissions'])
            ->where('guard_name', 'api')
            ->pluck('id', 'name')
            ->toArray();

        // Get all agent permissions
        $allAgentPermissions = DB::connection('tenant')
            ->table($tableNames['permissions'])
            ->where('guard_name', 'agent')
            ->pluck('id', 'name')
            ->toArray();

        // Get roles
        $ownerRoleId = DB::connection('tenant')->table($tableNames['roles'])->where('name', 'owner')->value('id');
        $adminRoleId = DB::connection('tenant')->table($tableNames['roles'])->where('name', 'admin')->value('id');
        $editorRoleId = DB::connection('tenant')->table($tableNames['roles'])->where('name', 'editor')->value('id');
        $readerRoleId = DB::connection('tenant')->table($tableNames['roles'])->where('name', 'reader')->value('id');
        $fieldAgentRoleId = DB::connection('tenant')->table($tableNames['roles'])->where('name', 'field-agent')->value('id');

        // Grant all API permissions to owner
        foreach ($allApiPermissions as $permissionId) {
            DB::connection('tenant')->table($tableNames['role_has_permissions'])->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $ownerRoleId,
            ]);
        }

        // Grant all API permissions to admin
        foreach ($allApiPermissions as $permissionId) {
            DB::connection('tenant')->table($tableNames['role_has_permissions'])->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ]);
        }

        // Grant editor permissions (all except admin-only)
        $editorPermissions = array_filter($allApiPermissions, fn($permissionId, $permissionName): bool => !in_array($permissionName, $adminOnlyPermissions), ARRAY_FILTER_USE_BOTH);

        foreach ($editorPermissions as $permissionId) {
            DB::connection('tenant')->table($tableNames['role_has_permissions'])->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $editorRoleId,
            ]);
        }

        // Grant reader permissions (only read/view permissions, excluding admin-only)
        $readerPermissions = array_filter($allApiPermissions, fn($permissionId, $permissionName): bool => str_ends_with($permissionName, '.view') && !in_array($permissionName, $adminOnlyPermissions), ARRAY_FILTER_USE_BOTH);

        foreach ($readerPermissions as $permissionId) {
            DB::connection('tenant')->table($tableNames['role_has_permissions'])->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $readerRoleId,
            ]);
        }

        // Grant agent permissions to field-agent role
        foreach ($allAgentPermissions as $permissionId) {
            DB::connection('tenant')->table($tableNames['role_has_permissions'])->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $fieldAgentRoleId,
            ]);
        }
    }
}
