<?php

namespace Database\Seeders;

use App\Services\CompanyService;
use App\Utils\DemoCompany;
use Illuminate\Console\View\Components\Info;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function __construct(
        private CompanyService $companyService,
    ) {}

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        // Demo data should only loaded into an empty database.
        if ($this->companyService->getAll()->isEmpty()) {
            $this->call([
                TenantSeeder::class,
                ClusterSeeder::class,
                CustomerSeeder::class,
                MeterSeeder::class,
                SolarHomeSystemSeeder::class,
                AgentSeeder::class,
                TicketSeeder::class,
                TransactionSeeder::class,
                SubConnectionTypeSeeder::class,
                SmsSeeder::class,
                TargetSeeder::class,
                PluginsSeeder::class,
                AgentApplianceSalesSeeder::class,
            ]);
        } else {
            // If the database already includes the Demo data we don't throw an error,
            // but just a warning to the user.
            // This is so that we can `php artisan db:seed` repeatedly.
            $demo_data_already_loaded = (
                $this->companyService->getById(DemoCompany::DEMO_COMPANY_ID)->name
                == DemoCompany::DEMO_COMPANY_NAME
            );
            if ($demo_data_already_loaded) {
                (new Info($this->command->getOutput()))->render(
                    'Demo data has been loaded previously. Nothing to seed.'
                );
            } else {
                throw new \Exception('There are already companies configured in MicroPowerManager. Demo data should only be loaded into an empty database. If you wish to reset existing setup with only Demo data, run `artisan migrate-tenant:drop-demo-company` and try again.');
            }
        }
    }
}
