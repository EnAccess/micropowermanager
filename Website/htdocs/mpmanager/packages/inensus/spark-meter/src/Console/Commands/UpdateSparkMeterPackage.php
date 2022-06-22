<?php


namespace Inensus\SparkMeter\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use Inensus\SparkMeter\Helpers\InsertSparkMeterApi;
use Inensus\SparkMeter\Services\CredentialService;
use Inensus\SparkMeter\Services\CustomerService;
use Inensus\SparkMeter\Services\MenuItemService;
use Inensus\SparkMeter\Services\MeterModelService;
use Inensus\SparkMeter\Services\PackageInstallationService;
use Inensus\SparkMeter\Services\SiteService;
use Inensus\SparkMeter\Services\SmSmsBodyService;
use Inensus\SparkMeter\Services\SmSmsFeedbackWordService;
use Inensus\SparkMeter\Services\SmSmsSettingService;
use Inensus\SparkMeter\Services\SmSmsVariableDefaultValueService;
use Inensus\SparkMeter\Services\SmSyncSettingService;

class UpdateSparkMeterPackage extends Command
{
    protected $signature = 'spark-meter:update';
    protected $description = 'Update the Spark Meter Integration Package';

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
    private $fileSystem;

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
     * @param Filesystem $filesystem
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
        PackageInstallationService $packageInstallationService,
        Filesystem $filesystem
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
        $this->fileSystem = $filesystem;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Spark Meter Integration Updating Started\n');
        $this->removeOldVersionOfPackage();
        $this->installNewVersionOfPackage();
        $this->deleteMigration($this->fileSystem);
        $this->publishMigrationsAgain();
        $this->updateDatabase();
        $this->publishVueFilesAgain();
        $this->packageInstallationService->createDefaultSettingRecords();
        $this->call('routes:generate');
        $this->createMenuItems();
        $this->call('sidebar:generate');
        $this->info('Package updated successfully..');
    }

    private function removeOldVersionOfPackage()
    {
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/spark-meter');
    }

    private function installNewVersionOfPackage()
    {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/spark-meter');
    }

    private function deleteMigration(Filesystem $filesystem)
    {
        $migrationFile = $filesystem->glob(database_path() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '*_create_spark_tables.php')[0];
        $migration = DB::table('migrations')
            ->where('migration', substr(explode("/migrations/", $migrationFile)[1], 0, -4))->first();
        if (!$migration) {
            return false;
        }
        return DB::table('migrations')
            ->where('migration', substr(explode("/migrations/", $migrationFile)[1], 0, -4))->delete();

    }

    private function publishMigrationsAgain()
    {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SparkMeter\Providers\SparkMeterServiceProvider",
            '--tag' => "migrations"
        ]);
    }

    private function updateDatabase()
    {
        $this->info('Updating database tables\n');
        $this->call('migrate');
    }

    private function publishVueFilesAgain()
    {
        $this->info('Updating vue files\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SparkMeter\Providers\SparkMeterServiceProvider",
            '--tag' => "vue-components",
            '--force' => true,
        ]);
    }

    private function createMenuItems()
    {
        $menuItems = $this->menuItemService->createMenuItems();
        $this->call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);
    }
}