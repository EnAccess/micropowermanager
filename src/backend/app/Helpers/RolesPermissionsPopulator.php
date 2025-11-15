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

        // Base permission set - simplified to domain-level permissions
        $permissions = [
            // user account management (fine-grained for role hierarchy)
            ['name' => 'users', 'guard_name' => 'api'],
            ['name' => 'users.manage-admin', 'guard_name' => 'api'],
            ['name' => 'users.manage-owner', 'guard_name' => 'api'],
            ['name' => 'settings', 'guard_name' => 'api'],
            ['name' => 'settings.api-keys', 'guard_name' => 'api'], // Only owner and admin
            ['name' => 'roles', 'guard_name' => 'api'],
            ['name' => 'horizon', 'guard_name' => 'api'],
            ['name' => 'customers', 'guard_name' => 'api'],
            ['name' => 'assets', 'guard_name' => 'api'],
            ['name' => 'tickets', 'guard_name' => 'api'],
            ['name' => 'payments', 'guard_name' => 'api'],
            ['name' => 'transactions', 'guard_name' => 'api'],
            ['name' => 'reports', 'guard_name' => 'api'],
            ['name' => 'exports', 'guard_name' => 'api'],
            ['name' => 'plugins', 'guard_name' => 'api'],
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
        // Cannot change system settings or manage users
        $financialManagerPermissionNames = [
            'customers',
            'assets',
            'tickets',
            'payments',
            'transactions',
            'reports',
            'exports',
        ];

        $financialManagerPermissions = array_filter($allApiPermissions, fn ($permissionId, $permissionName): bool => in_array($permissionName, $financialManagerPermissionNames), ARRAY_FILTER_USE_BOTH);

        foreach ($financialManagerPermissions as $permissionId) {
            DB::connection('tenant')->table($tableNames['role_has_permissions'])->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $financialManagerRoleId,
            ]);
        }

        // LEVEL 4: USER - Manage customers and related data only
        // Cannot access financial information, transactions, or system administration
        $userPermissionNames = [
            'customers',
            'assets',
            'tickets',
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
