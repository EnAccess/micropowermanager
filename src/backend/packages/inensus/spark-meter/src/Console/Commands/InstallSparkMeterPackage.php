<?php

namespace Inensus\SparkMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\SparkMeter\Helpers\InsertSparkMeterApi;
use Inensus\SparkMeter\Services\CredentialService;
use Inensus\SparkMeter\Services\CustomerService;
use Inensus\SparkMeter\Services\PackageInstallationService;
use Inensus\SparkMeter\Services\SiteService;

class InstallSparkMeterPackage extends Command {
    protected $signature = 'spark-meter:install';
    protected $description = 'Install the Spark Meter Integration Package';

    public function __construct(
        private InsertSparkMeterApi $insertSparkMeterApi,
        private CredentialService $credentialService,
        private CustomerService $customerService,
        private SiteService $siteService,
        private PackageInstallationService $packageInstallationService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing Spark Meter Integration Package\n');
        $this->packageInstallationService->createDefaultSettingRecords();
        $this->insertSparkMeterApi->registerSparkMeterManufacturer();
        $this->credentialService->createSmCredentials();
        $this->info('Package installed successfully..');
        $connections = $this->customerService->checkConnectionAvailability();
        if (!$this->siteService->checkLocationAvailability()) {
            $this->warn('------------------------------');
            $this->warn('Spark Meter package needs least one registered Cluster.');
            $this->warn('If you have no Cluster, please navigate to #Locations# section and register your locations.');
        }
        if (!$connections['type'] || !$connections['group']) {
            $this->warn('------------------------------');
            $this->warn('Spark Meter package needs least one Connection Group and one Connection Type.');
            $this->warn('Before you get Customers from Spark Meter please check them in #Connection# section.');
        }
    }
}
