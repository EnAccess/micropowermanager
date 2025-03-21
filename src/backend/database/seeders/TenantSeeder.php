<?php

namespace Database\Seeders;

use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\MainSettingsService;
use App\Services\RegistrationTailService;
use App\Services\UserService;
use App\Utils\DemoCompany;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder {
    public function __construct(
        private CompanyDatabaseService $companyDatabaseService,
        private CompanyService $companyService,
        private UserService $userService,
        private RegistrationTailService $registrationTailService,
        private MainSettingsService $mainSettingsService,
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
            'email' => 'demo_company@example.com',
            'protected_page_password' => DemoCompany::DEMO_COMPANY_PASSWORD,
        ]);

        $companyDatabase = $this->companyDatabaseService->create([
            'company_id' => $company->getId(),
            'database_name' => DemoCompany::DEMO_COMPANY_DATABASE_NAME,
        ]);

        // Create Admin user
        $this->companyService->runForCompany(
            $company->getId(),
            fn () => $this->userService->create(
                [
                    'name' => 'Demo Company Admin',
                    'email' => 'demo_company_admin@example.com',
                    'password' => DemoCompany::DEMO_COMPANY_PASSWORD,
                    'company_id' => $company->getId(),
                ],
                $company->getId()
            )
        );

        // Set some meaningful settings by default
        $this->companyService->runForCompany(
            $company->getId(),
            function () {
                $mainSettings = $this->mainSettingsService->getAll()->first();
                $this->mainSettingsService->update(
                    $mainSettings,
                    [
                        'company_name' => DemoCompany::DEMO_COMPANY_NAME,
                        'currency' => DemoCompany::DEMO_COMPANY_CURRENCY,
                    ]
                );
            }
        );

        // Plugin and Registration Tail magic
        // TBD: For now, only Registration Tail
        $this->companyService->runForCompany(
            $company->getId(),
            function () {
                // Do not prompt demo users to configure their default settings
                $registrationTail = [['tag' => 'Settings', 'component' => 'Settings', 'adjusted' => true]];

                $this->registrationTailService->create(['tail' => json_encode($registrationTail)]);
            }
        );
    }
}
