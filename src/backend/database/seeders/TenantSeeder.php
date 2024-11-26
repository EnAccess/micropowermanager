<?php

namespace Database\Seeders;

use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\MainSettingsService;
use App\Services\RegistrationTailService;
use App\Services\UserService;
use App\Utils\DummyCompany;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

const DUMMY_COMPANY_DATA = [
    'name' => 'Dummy Company',
    'address' => 'Dummy Address',
    'phone' => '+255123456789',
    'country_id' => -1,
    'email' => 'dummy_company@example.com',
    'protected_page_password' => '123123',
];
const DUMMY_COMPANY_SETTINGS = [
    'company_name' => 'Dummy Company',
    'currency' => 'TSZ',
];
const DUMMY_COMPANY_ADMIN = [
    'password' => '123123',
    'email' => 'dummy_company_admin@example.com',
    'name' => 'Dummy Company Admin',
];

class TenantSeeder extends Seeder {
    public function __construct(
        private CompanyDatabaseService $companyDatabaseService,
        private CompanyService $companyService,
        private UserService $userService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private RegistrationTailService $registrationTailService,
        private MainSettingsService $mainSettingsService,
    ) {}

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        // Only proceed if database is empty.
        // In some seeders we rely on the DummyCompany having id=1.
        $companies = $this->companyService->getAll();
        if ($companies->isNotEmpty()) {
            throw new \Exception('There are already companies configured in MicroPowerManager. Demo data should only be loaded into an empty database. If you wish to reset existing setup with only Demo data, run `artisan migrate-tenant:drop-demo-company` and try again.');
        }

        // Create Company and CompanyDatabase
        $company = $this->companyService->create(DUMMY_COMPANY_DATA);

        $companyDatabase = $this->companyDatabaseService->create([
            'company_id' => $company->getId(),
            'database_name' => DummyCompany::DUMMY_COMPANY_DATABASE_NAME,
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
                    [
                        'company_name' => DUMMY_COMPANY_SETTINGS['company_name'],
                        'currency' => DUMMY_COMPANY_SETTINGS['currency'],
                    ]
                );
            }
        );

        // Plugin and Registration Tail magic
        // TBD: For now, only Registration Tail
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function () {
                // Do not prompt demo users to configure their default settings
                $registrationTail = [['tag' => 'Settings', 'component' => 'Settings', 'adjusted' => true]];

                $this->registrationTailService->create(['tail' => json_encode($registrationTail)]);
            }
        );
    }
}
