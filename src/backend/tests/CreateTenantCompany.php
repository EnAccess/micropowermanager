<?php

namespace Tests;

use App\Helpers\RolesPermissionsPopulator;
use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\DatabaseProxyManagerService;

class TestCompany {
    public const TEST_COMPANY_NAME = 'Test Company';
    public const TEST_COMPANY_DATABASE_NAME = 'TestCompany_1';
    public const TEST_COMPANY_PASSWORD = '123123';
    public const TEST_COMPANY_ADMIN_EMAIL = 'test@example.com';
    public const TEST_COMPANY_ID = 1;
}

trait CreateTenantCompany {
    protected $companyId = 1;

    protected function setUpCreateTenantCompany(): void {
        $databaseConnections = config('database.connections');

        $databaseConnections['tenant'] = [
            'driver' => 'mysql',
            'host' => $databaseConnections['micro_power_manager']['host'],
            'port' => $databaseConnections['micro_power_manager']['port'],
            'database' => TestCompany::TEST_COMPANY_DATABASE_NAME,
            'username' => $databaseConnections['micro_power_manager']['username'],
            'password' => $databaseConnections['micro_power_manager']['password'],
            'unix_socket' => $databaseConnections['micro_power_manager']['unix_socket'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];

        config()->set('database.connections', $databaseConnections);

        $this->companyId = $this->createCompany();
    }

    public function createCompany(): string {
        $companyService = app(CompanyService::class);
        $companyDatabaseService = app(CompanyDatabaseService::class);
        $databaseProxyManagerService = app(DatabaseProxyManagerService::class);

        $company = $companyService->create([
            'name' => TestCompany::TEST_COMPANY_NAME,
            'address' => 'Sample Test Address',
            'phone' => '+255000456789',
            'country_id' => -1,
            'email' => TestCompany::TEST_COMPANY_ADMIN_EMAIL,
        ]);

        $companyDatabaseService->create([
            'company_id' => $company->getId(),
            'database_name' => TestCompany::TEST_COMPANY_DATABASE_NAME,
        ]);

        // Populate roles and permissions for the demo company
        $databaseProxyManagerService->runForCompany(
            $company->getId(),
            function (): void {
                RolesPermissionsPopulator::populate();
            }
        );

        return $company->getId();
    }
}
