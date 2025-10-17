<?php

namespace Database\Seeders;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Services\CompanyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class RoleBasePermissionSeedeer extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private CompanyService $companyService,
    ) {}

    public function run(): void {
        $companyIds = $this->companyService->getAll()->pluck('id');

        foreach ($companyIds as $companyId) {
            $this->databaseProxyManagerService->runForCompany($companyId, function (): void {
                $this->seedForTenant();
            });
        }
    }

    private function seedForTenant(): void {
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
            Permission::firstOrCreate($perm);
        }

        // Built-in roles: owner, admin (api guard), field-agent (agent guard)
        $owner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'api']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $fieldAgent = Role::firstOrCreate(['name' => 'field-agent', 'guard_name' => 'agent']);

        // Grant all to owner; admin gets all base permissions (can later restrict destructive ops)
        $allPermissions = Permission::where('guard_name', 'api')->pluck('name')->toArray();
        $owner->syncPermissions($allPermissions);
        $admin->syncPermissions($allPermissions);

        // Grant agent permissions to field-agent role
        $agentPermissions = Permission::where('guard_name', 'agent')->pluck('name')->toArray();
        $fieldAgent->syncPermissions($agentPermissions);

        try {
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            Log::info('permission:cache-reset not available: '.$e->getMessage());
        }
    }
}
