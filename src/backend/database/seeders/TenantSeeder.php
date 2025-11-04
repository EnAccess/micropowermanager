<?php

namespace Database\Seeders;

use App\Helpers\RolesPermissionsPopulator;
use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\MainSettingsService;
use App\Services\PluginsService;
use App\Services\UserService;
use App\Utils\DemoCompany;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class TenantSeeder extends Seeder {
    public function __construct(
        private CompanyDatabaseService $companyDatabaseService,
        private CompanyService $companyService,
        private UserService $userService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private MainSettingsService $mainSettingsService,
        private PluginsService $pluginsService,
    ) {}

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        // Create Company and CompanyDatabase
        $company = $this->companyService->create([
            'name' => DemoCompany::DEMO_COMPANY_NAME,
            'address' => 'Sample Address',
            'phone' => '+255123456789',
            'country_id' => -1,
            'email' => DemoCompany::DEMO_COMPANY_ADMIN_EMAIL,
        ]);

        $companyDatabase = $this->companyDatabaseService->create([
            'company_id' => $company->getId(),
            'database_name' => DemoCompany::DEMO_COMPANY_DATABASE_NAME,
        ]);

        // Populate roles and permissions for the demo company
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function (): void {
                RolesPermissionsPopulator::populate();
            }
        );

        // Create Admin user and assign owner role
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function () use ($company) {
                $owner = $this->userService->create(
                    [
                        'name' => 'Demo Company Admin',
                        'email' => DemoCompany::DEMO_COMPANY_ADMIN_EMAIL,
                        'password' => DemoCompany::DEMO_COMPANY_PASSWORD,
                        'company_id' => $company->getId(),
                    ],
                    $company->getId()
                );

                // Assign 'owner' role to the demo admin user
                $owner->assignRole('owner');
            }
        );

        // Create Editor user
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function () use ($company) {
                $editor = $this->userService->create(
                    [
                        'name' => 'Demo Manager',
                        'email' => DemoCompany::DEMO_COMPANY_FINANCIAL_MANAGER_EMAIL,
                        'password' => DemoCompany::DEMO_COMPANY_PASSWORD,
                        'company_id' => $company->getId(),
                    ],
                    $company->getId()
                );

                $editor->assignRole('financial-manager');
            }
        );

        // Create Reader user
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function () use ($company) {
                $reader = $this->userService->create(
                    [
                        'name' => 'Demo User',
                        'email' => DemoCompany::DEMO_COMPANY_REGULAR_USER_EMAIL,
                        'password' => DemoCompany::DEMO_COMPANY_PASSWORD,
                        'company_id' => $company->getId(),
                    ],
                    $company->getId()
                );

                $reader->assignRole('user');
            }
        );

        // Set some meaningful settings by default
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function () {
                $mainSettings = $this->mainSettingsService->getAll()->first();
                $this->mainSettingsService->update(
                    $mainSettings,
                    [
                        'company_name' => DemoCompany::DEMO_COMPANY_NAME,
                        'currency' => DemoCompany::DEMO_COMPANY_CURRENCY,
                        'protected_page_password' => DemoCompany::DEMO_COMPANY_PASSWORD,
                    ]
                );
            }
        );

        // Plugin and Registration Tail magic
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function () {
                $this->pluginsService->setupDemoManufacturerPlugins();
            }
        );
    }
}
