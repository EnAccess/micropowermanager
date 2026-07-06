<?php

namespace App\Services\ImportServices;

use App\Helpers\RolesPermissionsPopulator;
use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Models\User;
use App\Services\CompanyDatabaseService;
use App\Services\DatabaseProxyService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @extends AbstractImportService<UserImportItem>
 */
class UserPermissionImportService extends AbstractImportService {
    public function __construct(
        private UserService $userService,
        private DatabaseProxyService $databaseProxyService,
        private CompanyDatabaseService $companyDatabaseService,
    ) {}

    /**
     * @param list<UserImportItem> $data
     */
    public function import(array $data): ImportResult {
        // Ensure roles and permissions exist
        RolesPermissionsPopulator::populate();

        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($data as $item) {
                try {
                    $result = $this->importUser($item);
                    if ($result['success']) {
                        $imported[] = $result['user'];
                    } else {
                        $failed[] = [
                            'email' => $item->email,
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error importing user', [
                        'email' => $item->email,
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'email' => $item->email,
                        'errors' => ['import' => $e->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            $allFailed = count($imported) === 0 && count($failed) > 0;
            $partitioned = $this->partitionResults($imported);

            return new ImportResult(
                message: $allFailed ? 'All user imports failed' : 'Users imported successfully',
                added: $partitioned['added'],
                modified: $partitioned['modified'],
                failed: $failed,
            );
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->throwTransactionFailure('users', $e);
        }
    }

    /**
     * Import a single user with roles and permissions.
     *
     * @return array<string, mixed>
     */
    private function importUser(UserImportItem $item): array {
        // Find or create user
        $user = $this->userService->getByEmail($item->email);
        $isNew = !$user instanceof User;

        if ($isNew) {
            if ($item->password === null || $item->password === '') {
                return [
                    'success' => false,
                    'errors' => ['password' => 'Password is required for new users'],
                ];
            }

            $createData = [
                'name' => $item->name,
                'email' => $item->email,
                'password' => $item->password,
            ];

            if ($item->companyId !== null) {
                $createData['company_id'] = $item->companyId;
            }

            $user = $this->userService->create($createData);

            // Create database proxy entry if needed
            try {
                $companyDatabase = $this->companyDatabaseService->findByCompanyId($user->company_id);
                $databaseProxyData = [
                    'email' => $user->email,
                    'fk_company_id' => $user->company_id,
                    'fk_company_database_id' => $companyDatabase->id,
                ];
                $this->databaseProxyService->create($databaseProxyData);
            } catch (\Exception $e) {
                // Database proxy might already exist, continue
                Log::warning('Could not create database proxy for user', [
                    'email' => $item->email,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            $updateData = ['name' => $item->name];
            if ($item->password !== null && $item->password !== '') {
                $updateData['password'] = $item->password;
            }
            $this->userService->update($user, $updateData);
        }

        $roleNames = [];
        foreach ($item->roles as $roleItem) {
            $role = Role::firstOrCreate(['name' => $roleItem->name, 'guard_name' => 'api']);

            if ($roleItem->permissions !== null) {
                foreach ($roleItem->permissions as $permissionName) {
                    Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
                }
                $role->syncPermissions($roleItem->permissions);
            }

            $roleNames[] = $roleItem->name;
        }

        if ($roleNames !== []) {
            $user->syncRoles($roleNames);
        }

        foreach ($item->allPermissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
        }

        if ($item->allPermissions !== []) {
            $user->syncPermissions($item->allPermissions);
        }

        return [
            'success' => true,
            'action' => $isNew ? 'added' : 'modified',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'action' => $isNew ? 'added' : 'modified',
            ],
        ];
    }
}
