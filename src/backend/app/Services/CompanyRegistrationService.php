<?php

namespace App\Services;

use App\Exceptions\CompanyAlreadyExistsException;
use App\Exceptions\OwnerEmailAlreadyExistsException;
use App\Helpers\RolesPermissionsPopulator;
use App\Models\Company;
use App\Models\DatabaseProxy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyRegistrationService {
    public function __construct(
        private CompanyService $companyService,
        private CompanyDatabaseService $companyDatabaseService,
        private PluginsService $pluginsService,
        private UserService $userService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private MpmPluginService $mpmPluginService,
        private RegistrationTailService $registrationTailService,
        private MainSettingsService $mainSettingsService,
        private DatabaseProxy $databaseProxy,
    ) {}

    /**
     * Register a new company with all related data atomically.
     *
     * @param array{name: string, address: string, phone: string, email: string, country_id: int} $companyData
     * @param array{name: string, password: string, email: string}                                $adminData
     * @param array<array{id: int}>                                                               $plugins
     *
     * @throws CompanyAlreadyExistsException
     * @throws OwnerEmailAlreadyExistsException
     * @throws \Exception
     */
    public function register(
        array $companyData,
        array $adminData,
        array $plugins,
        string $usageType,
    ): Company {
        // Pre-validation: Check if owner email already exists before any database operations
        $this->validateOwnerEmailDoesNotExist($adminData['email']);

        $company = null;
        $databaseName = null;
        $databaseCreated = false;

        try {
            // Step 1: Create Company and CompanyDatabase record in main database transaction
            DB::connection('micro_power_manager')->beginTransaction();

            $company = $this->createCompany($companyData);
            $databaseName = $this->generateDatabaseName($company->getName());

            $this->createCompanyDatabaseRecord($company->getId(), $databaseName);

            DB::connection('micro_power_manager')->commit();

            // Step 2: Create the physical database (DDL - cannot be in transaction)
            $this->createPhysicalDatabase($databaseName);
            $databaseCreated = true;

            // Step 3: Run migrations for the tenant database
            $this->runTenantMigrations($company->getId());

            // Step 4: Setup tenant data in a transaction
            DB::connection('micro_power_manager')->beginTransaction();
            $this->setupTenantData($company, $adminData, $plugins, $usageType);
            DB::connection('micro_power_manager')->commit();

            return $company;
        } catch (\Exception $e) {
            $this->rollbackTransactions();

            if ($databaseCreated && $databaseName) {
                $this->cleanupDatabase($databaseName);
            }

            if ($company instanceof Company) {
                $this->cleanupCompanyRecords($company->getId());
            }

            Log::error('Company registration failed', [
                'error' => $e->getMessage(),
                'company_data' => $companyData,
                'admin_email' => $adminData['email'],
            ]);

            throw $e;
        }
    }

    /**
     * Validate that the owner email doesn't already exist in the database proxy.
     *
     * @throws OwnerEmailAlreadyExistsException
     */
    private function validateOwnerEmailDoesNotExist(string $email): void {
        $existingProxy = $this->databaseProxy->newQuery()
            ->where('email', $email)
            ->first();

        if ($existingProxy !== null) {
            throw new OwnerEmailAlreadyExistsException('Owner account email already exists');
        }
    }

    /**
     * Create the company record.
     *
     * @param array<string, mixed> $companyData
     *
     * @throws CompanyAlreadyExistsException
     */
    private function createCompany(array $companyData): Company {
        return $this->companyService->create($companyData);
    }

    /**
     * Generate a sanitized database name from company name.
     */
    private function generateDatabaseName(string $companyName): string {
        return str_replace(' ', '', preg_replace('/[^a-z\d_ ]/i', '', $companyName)).'_'.
            Carbon::now()->timestamp;
    }

    /**
     * Create the CompanyDatabase record.
     */
    private function createCompanyDatabaseRecord(int $companyId, string $databaseName): void {
        $this->companyDatabaseService->createRecord([
            'company_id' => $companyId,
            'database_name' => $databaseName,
        ]);
    }

    /**
     * Create the physical database.
     */
    private function createPhysicalDatabase(string $databaseName): void {
        DB::unprepared("CREATE DATABASE IF NOT EXISTS $databaseName");
    }

    /**
     * Run migrations for the tenant database.
     */
    private function runTenantMigrations(int $companyId): void {
        $this->databaseProxyManagerService->runForCompany(
            $companyId,
            function () {
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => '/database/migrations/tenant',
                    '--force' => true,
                ]);
            }
        );
    }

    /**
     * Setup all tenant data (roles, users, settings, plugins) within a transaction.
     *
     * @param array{name: string, password: string, email: string} $adminData
     * @param array<array{id: int}>                                $plugins
     */
    private function setupTenantData(
        Company $company,
        array $adminData,
        array $plugins,
        string $usageType,
    ): void {
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function () use ($company, $adminData, $plugins, $usageType) {
                DB::connection('tenant')->beginTransaction();

                try {
                    RolesPermissionsPopulator::populate();

                    // Create admin user (this also triggers DatabaseProxy creation via event)
                    $adminUser = $this->userService->create(
                        [
                            'name' => $adminData['name'],
                            'password' => $adminData['password'],
                            'email' => $adminData['email'],
                            'company_id' => $company->getId(),
                        ],
                        $company->getId()
                    );

                    $adminUser->assignRole('owner');

                    $mainSettings = $this->mainSettingsService->getAll()->first();
                    $this->mainSettingsService->update(
                        $mainSettings,
                        ['company_name' => $company->name, 'usage_type' => $usageType]
                    );

                    $this->setupPluginsAndRegistrationTail($plugins);

                    DB::connection('tenant')->commit();
                } catch (\Exception $e) {
                    DB::connection('tenant')->rollBack();
                    throw $e;
                }
            }
        );
    }

    /**
     * Setup plugins and registration tail.
     *
     * @param array<array{id: int}> $plugins
     */
    private function setupPluginsAndRegistrationTail(array $plugins): void {
        $registrationTail = [['tag' => 'Settings', 'component' => 'Settings', 'adjusted' => false]];

        foreach ($plugins as $plugin) {
            $pluginData = [
                'mpm_plugin_id' => $plugin['id'],
                'status' => 1,
            ];
            $this->pluginsService->create($pluginData);

            $mpmPlugin = $this->mpmPluginService->getById($plugin['id']);
            $registrationTail[] = [
                'tag' => $mpmPlugin->tail_tag,
                'component' => isset($mpmPlugin->tail_tag) ? str_replace(
                    ' ',
                    '-',
                    $mpmPlugin->tail_tag
                ) : null,
                'adjusted' => !isset($mpmPlugin->tail_tag),
            ];
            Artisan::call($mpmPlugin->installation_command);
        }

        $this->registrationTailService->create(['tail' => json_encode($registrationTail)]);
    }

    /**
     * Rollback any open transactions on both connections.
     */
    private function rollbackTransactions(): void {
        try {
            DB::connection('micro_power_manager')->rollBack();
        } catch (\Exception) {
            // Transaction may not be active, ignore
        }

        try {
            DB::connection('tenant')->rollBack();
        } catch (\Exception) {
            // Transaction may not be active, ignore
        }
    }

    /**
     * Clean up the created database on failure.
     */
    private function cleanupDatabase(string $databaseName): void {
        try {
            DB::unprepared("DROP DATABASE IF EXISTS $databaseName");
            Log::info('Cleaned up database after failed registration', ['database' => $databaseName]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup database after failed registration', [
                'database' => $databaseName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clean up company records if they were created before failure.
     */
    private function cleanupCompanyRecords(int $companyId): void {
        try {
            // Delete in reverse order of creation due to foreign key constraints
            DB::connection('micro_power_manager')
                ->table('database_proxies')
                ->where('fk_company_id', $companyId)
                ->delete();

            DB::connection('micro_power_manager')
                ->table('company_databases')
                ->where('company_id', $companyId)
                ->delete();

            DB::connection('micro_power_manager')
                ->table('companies')
                ->where('id', $companyId)
                ->delete();

            Log::info('Cleaned up company records after failed registration', ['company_id' => $companyId]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup company records after failed registration', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
