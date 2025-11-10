<?php

namespace Database\Seeders;

use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\MainSettingsService;
use App\Services\PluginsService;
use App\Services\RegistrationTailService;
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

        // Create Admin user and DatabaseProxy
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            fn () => $this->userService->create(
                [
                    'name' => 'Demo Company Admin',
                    'email' => DemoCompany::DEMO_COMPANY_ADMIN_EMAIL,
                    'password' => DemoCompany::DEMO_COMPANY_PASSWORD,
                    'company_id' => $company->getId(),
                ],
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
