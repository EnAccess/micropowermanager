<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class RolesPermissionsPopulator {
    public static function populate(): void {
        $tableNames = config('permission.table_names');

        // Check if roles and permissions already exist - if so, skip population
        $existingRolesCount = DB::connection('tenant')
            ->table($tableNames['roles'])
            ->whereIn('name', ['owner', 'admin', 'financial-manager', 'user'])
            ->count();

        if ($existingRolesCount >= 4) {
            // All roles already exist, skip population
            return;
        }

        // Base permission set
        $permissions = [
            // user account management
            ['name' => 'users.view', 'guard_name' => 'api'],
            ['name' => 'users.create', 'guard_name' => 'api'],
            ['name' => 'users.update', 'guard_name' => 'api'],
            ['name' => 'users.delete', 'guard_name' => 'api'],
            ['name' => 'users.manage-owner', 'guard_name' => 'api'], // Only owner can manage owner accounts
            ['name' => 'users.manage-admin', 'guard_name' => 'api'], // Owner and admin can manage admin accounts
            // settings
            ['name' => 'settings.view', 'guard_name' => 'api'],
            ['name' => 'settings.update', 'guard_name' => 'api'],
            ['name' => 'settings.api-keys', 'guard_name' => 'api'], // API keys management (admin only)
            ['name' => 'settings.passwords', 'guard_name' => 'api'], // Password management (admin only)
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
            // payments/transactions (financial data)
            ['name' => 'payments.view', 'guard_name' => 'api'],
            ['name' => 'payments.create', 'guard_name' => 'api'],
            ['name' => 'payments.refund', 'guard_name' => 'api'],
            ['name' => 'transactions.view', 'guard_name' => 'api'],
            ['name' => 'transactions.create', 'guard_name' => 'api'],
            // reports & exports
            ['name' => 'reports.view', 'guard_name' => 'api'],
            ['name' => 'exports.transactions', 'guard_name' => 'api'],
            ['name' => 'exports.customers', 'guard_name' => 'api'],
            ['name' => 'exports.debts', 'guard_name' => 'api'],
            // plugins
            ['name' => 'plugins.manage', 'guard_name' => 'api'],
            // pages
            ['name' => 'page.view:/settings', 'guard_name' => 'api'],
        ];

        foreach ($permissions as $perm) {
            DB::connection('tenant')->table($tableNames['permissions'])->insertOrIgnore([
                'name' => $perm['name'],
                'guard_name' => $perm['guard_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Built-in roles based on hierarchy:
        // Level 1: Owner - Full control, including managing administrators
        // Level 2: Administrator - Manage all data/settings except owner accounts
        // Level 3: Financial Manager - Manage customers + financial data, no system settings
        // Level 4: User - Manage customers, no financial data
        $roles = [
            ['name' => 'owner', 'guard_name' => 'api', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin', 'guard_name' => 'api', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'financial-manager', 'guard_name' => 'api', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'user', 'guard_name' => 'api', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($roles as $role) {
            DB::connection('tenant')->table($tableNames['roles'])->insertOrIgnore($role);
        }

        // Get all API permissions
        $allApiPermissions = DB::connection('tenant')
            ->table($tableNames['permissions'])
            ->where('guard_name', 'api')
            ->pluck('id', 'name')
            ->toArray();

        // Get roles
        $ownerRoleId = DB::connection('tenant')->table($tableNames['roles'])->where('name', 'owner')->value('id');
        $adminRoleId = DB::connection('tenant')->table($tableNames['roles'])->where('name', 'admin')->value('id');
        $financialManagerRoleId = DB::connection('tenant')->table($tableNames['roles'])->where('name', 'financial-manager')->value('id');
        $userRoleId = DB::connection('tenant')->table($tableNames['roles'])->where('name', 'user')->value('id');

        // LEVEL 1: OWNER - Full control over the entire system
        // Can manage all accounts including creating/removing administrators
        // Cannot delete other owners (enforced at application level)
        foreach ($allApiPermissions as $permissionId) {
            DB::connection('tenant')->table($tableNames['role_has_permissions'])->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $ownerRoleId,
            ]);
        }

        // LEVEL 2: ADMINISTRATOR - Manage all system data and settings
        // Can manage admin accounts and lower levels, but NOT owner accounts
        $adminPermissions = array_filter($allApiPermissions, fn ($permissionId, $permissionName): bool => $permissionName !== 'users.manage-owner', ARRAY_FILTER_USE_BOTH);

        foreach ($adminPermissions as $permissionId) {
            DB::connection('tenant')->table($tableNames['role_has_permissions'])->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ]);
        }

        // LEVEL 3: FINANCIAL MANAGER - Manage customers + financial/transaction data
        // Cannot change system settings like API keys or passwords
        $financialManagerPermissionNames = [
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',
            'assets.view',
            'assets.create',
            'assets.update',
            'assets.delete',
            'tickets.view',
            'tickets.create',
            'tickets.update',
            'tickets.delete',
            'tickets.export',
            'payments.view',
            'payments.create',
            'payments.refund',
            'transactions.view',
            'transactions.create',
            'reports.view',
            'exports.transactions',
            'exports.customers',
            'exports.debts',
        ];

        $financialManagerPermissions = array_filter($allApiPermissions, fn ($permissionId, $permissionName): bool => in_array($permissionName, $financialManagerPermissionNames), ARRAY_FILTER_USE_BOTH);

        foreach ($financialManagerPermissions as $permissionId) {
            DB::connection('tenant')->table($tableNames['role_has_permissions'])->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $financialManagerRoleId,
            ]);
        }

        // LEVEL 4: USER - Manage customers and related data only
        // Cannot access financial or transaction information
        $userPermissionNames = [
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',
            'assets.view',
            'assets.create',
            'assets.update',
            'assets.delete',
            'tickets.view',
            'tickets.create',
            'tickets.update',
            'tickets.delete',
            'tickets.export',
        ];

        $userPermissions = array_filter($allApiPermissions, fn ($permissionId, $permissionName): bool => in_array($permissionName, $userPermissionNames), ARRAY_FILTER_USE_BOTH);

        foreach ($userPermissions as $permissionId) {
            DB::connection('tenant')->table($tableNames['role_has_permissions'])->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $userRoleId,
            ]);
        }
    }
}
