<?php

namespace Inensus\SparkMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\SparkMeter\Helpers\InsertSparkMeterApi;
use Inensus\SparkMeter\Services\CredentialService;
use Inensus\SparkMeter\Services\CustomerService;
use Inensus\SparkMeter\Services\MeterModelService;
use Inensus\SparkMeter\Services\MenuItemService;
use Inensus\SparkMeter\Services\PackageInstallationService;
use Inensus\SparkMeter\Services\SiteService;
use Inensus\SparkMeter\Services\SmSmsBodyService;
use Inensus\SparkMeter\Services\SmSmsFeedbackWordService;
use Inensus\SparkMeter\Services\SmSmsSettingService;
use Inensus\SparkMeter\Services\SmSmsVariableDefaultValueService;
use Inensus\SparkMeter\Services\SmSyncSettingService;

class InstallSparkMeterPackage extends Command
{
    protected $signature = 'spark-meter:install';
    protected $description = 'Install the Spark Meter Integration Package';

    private $insertSparkMeterApi;
    private $meterModelService;
    private $credentialService;
    private $menuItemService;
    private $customerService;
    private $siteService;
    private $smsSettingService;
    private $syncSettingService;
    private $smsBodyService;
    private $defaultValueService;
    private $smSmsFeedbackWordService;
    private $packageInstallationService;

    /**
     * Create a new command instance.
     *
     * @param InsertSparkMeterApi $insertSparkMeterApi
     * @param MeterModelService $meterModelService
     * @param CredentialService $credentialService
     * @param MenuItemService $menuItemService
     * @param CustomerService $customerService
     * @param SiteService $siteService
     * @param SmSmsSettingService $smsSettingService
     * @param SmSyncSettingService $syncSettingService
     * @param SmSmsBodyService $smsBodyService
     * @param SmSmsVariableDefaultValueService $defaultValueService
     * @param SmSmsFeedbackWordService $smSmsFeedbackWordService
     * @param PackageInstallationService $packageInstallationService
     */
    public function __construct(
        InsertSparkMeterApi $insertSparkMeterApi,
        MeterModelService $meterModelService,
        CredentialService $credentialService,
        MenuItemService $menuItemService,
        CustomerService $customerService,
        SiteService $siteService,
        SmSmsSettingService $smsSettingService,
        SmSyncSettingService $syncSettingService,
        SmSmsBodyService $smsBodyService,
        SmSmsVariableDefaultValueService $defaultValueService,
        SmSmsFeedbackWordService $smSmsFeedbackWordService,
        PackageInstallationService $packageInstallationService
    ) {
        parent::__construct();
        $this->insertSparkMeterApi = $insertSparkMeterApi;
        $this->meterModelService = $meterModelService;
        $this->credentialService = $credentialService;
        $this->menuItemService = $menuItemService;
        $this->customerService = $customerService;
        $this->siteService = $siteService;
        $this->smsSettingService = $smsSettingService;
        $this->syncSettingService = $syncSettingService;
        $this->smsBodyService = $smsBodyService;
        $this->defaultValueService = $defaultValueService;
        $this->smSmsFeedbackWordService = $smSmsFeedbackWordService;
        $this->packageInstallationService = $packageInstallationService;
    }

    public function handle(): void
    {
        $this->info('Installing Spark Meter Integration Package\n');
        $this->publishMigrations();
        $this->createDatabaseTables();
        $this->packageInstallationService->createDefaultSettingRecords();
        $this->publishVueFiles();
        $this->insertSparkMeterApi->registerSparkMeterManufacturer();
        $this->credentialService->createSmCredentials();
        $this->createPluginRecord();
        $this->call('routes:generate');
        $this->createMenuItems();
        $this->call('sidebar:generate');
        $this->info('Package installed successfully..');
        $connections = $this->customerService->checkConnectionAvailability();
        if (!$this->siteService->checkLocationAvailability()) {
            $this->warn('------------------------------');
            $this->warn("Spark Meter package needs least one registered Cluster.");
            $this->warn("If you have no Cluster, please navigate to #Locations# section and register your locations.");
        }
        if (!$connections['type'] || !$connections['group']) {
            $this->warn('------------------------------');
            $this->warn("Spark Meter package needs least one Connection Group and one Connection Type.");
            $this->warn("Before you get Customers from Spark Meter please check them in #Connection# section.");
        }
    }
    private function publishMigrations(){
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SparkMeter\Providers\SparkMeterServiceProvider",
            '--tag' => "migrations"
        ]);
    }
    private function createDatabaseTables()
    {
        $this->info('Creating database tables\n');
        $this->call('migrate');
    }
    private function publishVueFiles(){

        $this->info('Copying vue files\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SparkMeter\Providers\SparkMeterServiceProvider",
            '--tag' => "vue-components",
            '--force' => true,
        ]);
    }
    private function createPluginRecord(){
        $this->call('plugin:add', [
            'name' => "SparkMeter",
            'composer_name' => "inensus/spark-meter",
            'description' => "Spark meters integration package for MicroPowerManager",
        ]);
    }
    private function createMenuItems(){
        $menuItems = $this->menuItemService->createMenuItems();
        if (array_key_exists('menuItem', $menuItems)) {
            $this->call('menu-items:generate', [
                'menuItem' => $menuItems['menuItem'],
                'subMenuItems' => $menuItems['subMenuItems'],
            ]);
        }
    }
}
