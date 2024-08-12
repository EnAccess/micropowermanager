<?php

namespace Database\Seeders;

use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\MainSettingsService;
use App\Services\UserService;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

const DUMMY_COMPANY_DATA = [
    'name' => 'Dummy Company',
    'address' => 'Dummy Address',
    'phone' => '+255123456789',
    // 'phone' => fake()->unique()->e164PhoneNumber(),
    'country_id' => -1,
    'email' => 'dummy_company@example.com',
    'protected_page_password' => '123123',
];
const DUMMY_COMPANY_ADMIN = [
    'password' => '123123',
    'email' => 'dummy_company_admin@example.com',
    'name' => 'Dummy Company Admin',
];
const DUMMY_DATABASE_NAME = 'DummyCompany_1';

class TenantSeeder extends Seeder
{
    public function __construct(
        private CompanyDatabaseService $companyDatabaseService,
        private CompanyService $companyService,
        private UserService $userService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private MainSettingsService $mainSettingsService
    ) {
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Company and CompanyDatabase
        $company = $this->companyService->create(DUMMY_COMPANY_DATA);

        $companyDatabase = $this->companyDatabaseService->create([
            'company_id' => $company->getId(),
            'database_name' => DUMMY_DATABASE_NAME,
        ]);

        // Create Admin user and DatabaseProxy
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            fn () => $this->userService->create(
                DUMMY_COMPANY_ADMIN + ['company_id' => $company->getId()],
                $company->getId()
            )
        );

        // Set some meaningful settings by default
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function () {
                $mainSettings = $this->mainSettingsService->getAll()->first();
                $this->mainSettingsService->update(
                    $mainSettings,
                    ['company_name' => DUMMY_COMPANY_ADMIN['name']]
                );
            }
        );
    }
}
