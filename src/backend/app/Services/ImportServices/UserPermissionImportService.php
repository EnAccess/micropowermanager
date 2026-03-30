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

class UserPermissionImportService extends AbstractImportService {
    public function __construct(
        private UserService $userService,
        private DatabaseProxyService $databaseProxyService,
        private CompanyDatabaseService $companyDatabaseService,
    ) {}

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function import(array $data): array {
        // Handle export format: data might be wrapped in 'data' key or direct array
        $importData = $data;
        if (isset($data['data']) && is_array($data['data'])) {
            $importData = $data['data'];
        }

        $errors = $this->validate($importData);
        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        // Ensure roles and permissions exist
        RolesPermissionsPopulator::populate();

        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($importData as $userData) {
                try {
                    $result = $this->importUser($userData);
                    if ($result['success']) {
                        $imported[] = $result['user'];
                    } else {
                        $failed[] = [
                            'email' => $userData['email'] ?? 'unknown',
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error importing user', [
                        'email' => $userData['email'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'email' => $userData['email'] ?? 'unknown',
                        'errors' => ['import' => $e->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            return [
                'success' => true,
                'message' => 'Users imported successfully',
                'imported_count' => count($imported),
                'failed_count' => count($failed),
                'imported' => $imported,
                'failed' => $failed,
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Error during user import transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'errors' => ['transaction' => 'Failed to import users: '.$e->getMessage()],
            ];
        }
    }

    /**
     * Import a single user with roles and permissions.
     *
     * @param array<string, mixed> $userData
     *
     * @return array<string, mixed>
     */
    private function importUser(array $userData): array {
        // Validate required fields
        if (empty($userData['email'])) {
            return [
                'success' => false,
                'errors' => ['email' => 'Email is required'],
            ];
        }

        if (empty($userData['name'])) {
            return [
                'success' => false,
                'errors' => ['name' => 'Name is required'],
            ];
        }

        // Find or create user
        $user = $this->userService->getByEmail($userData['email']);

        if (!$user instanceof User) {
            // Create new user
            if (empty($userData['password'])) {
                return [
                    'success' => false,
                    'errors' => ['password' => 'Password is required for new users'],
                ];
            }

            $createData = [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
            ];

            // Handle company_id if provided (matches export format)
            if (isset($userData['company_id'])) {
                $createData['company_id'] = $userData['company_id'];
            }

            $user = $this->userService->create($createData);

            // Create database proxy entry if needed
            try {
                $companyDatabase = $this->companyDatabaseService->findByCompanyId($user->getCompanyId());
                $databaseProxyData = [
                    'email' => $user->getEmail(),
                    'fk_company_id' => $user->getCompanyId(),
                    'fk_company_database_id' => $companyDatabase->getId(),
                ];
                $this->databaseProxyService->create($databaseProxyData);
            } catch (\Exception $e) {
                // Database proxy might already exist, continue
                Log::warning('Could not create database proxy for user', [
                    'email' => $userData['email'],
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // Update existing user (name validated above)
            $updateData = ['name' => $userData['name']];
            if (isset($userData['password']) && $userData['password'] !== '') {
                $updateData['password'] = $userData['password'];
            }
            $this->userService->update($user, $updateData);
        }

        // Handle roles - matches export format: array of {name, permissions}
        if (isset($userData['roles']) && is_array($userData['roles'])) {
            $roleNames = [];
            foreach ($userData['roles'] as $roleData) {
                if (is_string($roleData)) {
                    $roleNames[] = $roleData;
                } elseif (is_array($roleData) && isset($roleData['name'])) {
                    $roleNames[] = $roleData['name'];

                    // Ensure role permissions exist
                    if (isset($roleData['permissions']) && is_array($roleData['permissions'])) {
                        foreach ($roleData['permissions'] as $permissionName) {
                            if (is_string($permissionName)) {
                                Permission::firstOrCreate(
                                    ['name' => $permissionName, 'guard_name' => 'api']
                                );
                            }
                        }
                    }
                }
            }

            // Ensure all roles exist
            foreach ($roleNames as $roleName) {
                $role = Role::firstOrCreate(
                    ['name' => $roleName, 'guard_name' => 'api']
                );

                // Sync role permissions if provided
                foreach ($userData['roles'] as $roleData) {
                    if (is_array($roleData) && isset($roleData['name']) && $roleData['name'] === $roleName && (isset($roleData['permissions']) && is_array($roleData['permissions']))) {
                        $permissionNames = array_filter($roleData['permissions'], is_string(...));
                        $role->syncPermissions($permissionNames);
                    }
                }
            }

            if ($roleNames !== []) {
                $user->syncRoles($roleNames);
            }
        }

        // Handle all_permissions - matches export format: array of permission names
        if (isset($userData['all_permissions']) && is_array($userData['all_permissions'])) {
            $permissionNames = [];
            foreach ($userData['all_permissions'] as $permissionName) {
                if (is_string($permissionName)) {
                    Permission::firstOrCreate(
                        ['name' => $permissionName, 'guard_name' => 'api']
                    );
                    $permissionNames[] = $permissionName;
                }
            }

            if ($permissionNames !== []) {
                $user->syncPermissions($permissionNames);
            }
        }

        return [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    public function validate(array $data): array {
        $errors = [];

        // Validate each user entry
        foreach ($data as $index => $userData) {
            if (!is_array($userData)) {
                $errors["user_{$index}"] = 'User data must be an array';
                continue;
            }

            if (empty($userData['email'])) {
                $errors["user_{$index}.email"] = 'Email is required';
            } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors["user_{$index}.email"] = 'Email must be valid';
            }

            if (empty($userData['name'])) {
                $errors["user_{$index}.name"] = 'Name is required';
            }
        }

        return $errors;
    }
}
