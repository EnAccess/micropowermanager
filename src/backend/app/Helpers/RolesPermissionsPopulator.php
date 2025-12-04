<?php

namespace App\Helpers;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;

class RolesPermissionsPopulator {
    public static function populate(): void {
        // Check if roles and permissions already exist - if so, skip population
        $existingRolesCount = Role::whereIn('name', ['owner', 'admin', 'financial-manager', 'user'])->count();

        if ($existingRolesCount >= 4) {
            // All roles already exist, skip population
            return;
        }

        // Base permission set - simplified to domain-level permissions
        $permissionNames = [
            // user account management (fine-grained for role hierarchy)
            'users',
            'users.manage-admin',
            'users.manage-owner',
            'settings',
            'settings.api-keys', // Only owner and admin
            'roles',
            'horizon',
            'customers',
            'appliances',
            'tickets',
            'payments',
            'transactions',
            'reports',
            'exports',
            'plugins',
        ];

        foreach ($permissionNames as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'api']
            );
        }

        // Built-in roles based on hierarchy:
        // Level 1: Owner - Full control, including managing administrators
        // Level 2: Administrator - Manage all data/settings except owner accounts
        // Level 3: Financial Manager - Manage customers + financial data, no system settings
        // Level 4: User - Manage customers, no financial data

        // LEVEL 1: OWNER - Full control over the entire system
        // Can manage all accounts including creating/removing administrators
        // Cannot delete other owners (enforced at application level)
        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'api']);
        $allPermissions = Permission::where('guard_name', 'api')->get();
        $ownerRole->syncPermissions($allPermissions);

        // LEVEL 2: ADMINISTRATOR - Manage all system data and settings
        // Can manage admin accounts and lower levels, but NOT owner accounts
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminPermissions = Permission::where('guard_name', 'api')
            ->where('name', '!=', 'users.manage-owner')
            ->get();
        $adminRole->syncPermissions($adminPermissions);

        // LEVEL 3: FINANCIAL MANAGER - Manage customers + financial/transaction data
        // Cannot change system settings or manage users
        $financialManagerRole = Role::firstOrCreate(['name' => 'financial-manager', 'guard_name' => 'api']);
        $financialManagerPermissionNames = [
            'customers',
            'appliances',
            'tickets',
            'payments',
            'transactions',
            'reports',
            'exports',
        ];
        $financialManagerPermissions = Permission::whereIn('name', $financialManagerPermissionNames)
            ->where('guard_name', 'api')
            ->get();
        $financialManagerRole->syncPermissions($financialManagerPermissions);

        // LEVEL 4: USER - Manage customers and related data only
        // Cannot access financial information, transactions, or system administration
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        $userPermissionNames = [
            'customers',
            'appliances',
            'tickets',
        ];
        $userPermissions = Permission::whereIn('name', $userPermissionNames)
            ->where('guard_name', 'api')
            ->get();
        $userRole->syncPermissions($userPermissions);
    }
}
