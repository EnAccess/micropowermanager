<?php

namespace Inensus\SteamaMeter\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Inensus\SteamaMeter\Helpers\ApiHelpers;
use Inensus\SteamaMeter\Services\MenuItemService;
use Inensus\SteamaMeter\Services\PackageInstallationService;
use Inensus\SteamaMeter\Services\SteamaAgentService;
use Inensus\SteamaMeter\Services\SteamaCredentialService;
use Inensus\SteamaMeter\Services\SteamaSiteLevelPaymentPlanTypeService;
use Inensus\SteamaMeter\Services\SteamaSiteService;
use Inensus\SteamaMeter\Services\SteamaSmsBodyService;
use Inensus\SteamaMeter\Services\SteamaSmsFeedbackWordService;
use Inensus\SteamaMeter\Services\SteamaSmsSettingService;
use Inensus\SteamaMeter\Services\SteamaSmsVariableDefaultValueService;
use Inensus\SteamaMeter\Services\SteamaSyncSettingService;
use Inensus\SteamaMeter\Services\SteamaTariffService;
use Inensus\SteamaMeter\Services\SteamaUserTypeService;

class UpdatePackage extends Command
{
    protected $signature = 'steama-meter:update';
    protected $description = 'Install Steamaco Meter Package';

    private $menuItemService;
    private $agentService;
    private $credentialService;
    private $paymentPlanService;
    private $tariffService;
    private $userTypeService;
    private $apiHelpers;
    private $siteService;
    private $smsSettingService;
    private $syncSettingService;
    private $smsBodyService;
    private $defaultValueService;
    private $steamaSmsFeedbackWordService;
    private $packageInstallationService;
    private $fileSystem;

    public function __construct(
        MenuItemService $menuItemService,
        SteamaAgentService $agentService,
        SteamaCredentialService $credentialService,
        SteamaSiteLevelPaymentPlanTypeService $paymentPlanService,
        SteamaTariffService $tariffService,
        SteamaUserTypeService $userTypeService,
        ApiHelpers $apiHelpers,
        SteamaSiteService $siteService,
        SteamaSmsSettingService $smsSettingService,
        SteamaSyncSettingService $syncSettingService,
        SteamaSmsBodyService $smsBodyService,
        SteamaSmsVariableDefaultValueService $defaultValueService,
        SteamaSmsFeedbackWordService $steamaSmsFeedbackWordService,
        PackageInstallationService $packageInstallationService,
        Filesystem $fileSystem
    ) {
        parent::__construct();
        $this->apiHelpers = $apiHelpers;
        $this->menuItemService = $menuItemService;
        $this->agentService = $agentService;
        $this->credentialService = $credentialService;
        $this->paymentPlanService = $paymentPlanService;
        $this->tariffService = $tariffService;
        $this->userTypeService = $userTypeService;
        $this->siteService = $siteService;
        $this->smsSettingService = $smsSettingService;
        $this->syncSettingService = $syncSettingService;
        $this->smsBodyService = $smsBodyService;
        $this->defaultValueService = $defaultValueService;
        $this->steamaSmsFeedbackWordService = $steamaSmsFeedbackWordService;
        $this->packageInstallationService = $packageInstallationService;
        $this->fileSystem = $fileSystem;
    }

    public function handle(): void
    {
        $this->info('Steamaco Meter Integration Updating Started\n');

        $this->removeOldVersionOfPackage();
        $this->installNewVersionOfPackage();
        $this->deleteMigration($this->fileSystem);
        $this->publishMigrationsAgain();
        $this->updateDatabase();
        $this->publishVueFilesAgain();
        $this->packageInstallationService->createDefaultSettingRecords();
        $this->call('routes:generate');
        $this->createMenuItems();
        $this->info('Package updated successfully..');
    }

    private function removeOldVersionOfPackage()
    {
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/steama-meter');
    }

    private function installNewVersionOfPackage()
    {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/steama-meter');
    }

    private function deleteMigration(Filesystem $filesystem)
    {
        $migrationFile = $filesystem->glob(database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*_create_steama_tables.php')[0];
        $migration = DB::table('migrations')
            ->where('migration', substr(explode('/migrations/', $migrationFile)[1], 0, -4))->first();
        if (!$migration) {
            return false;
        }

        return DB::table('migrations')
            ->where('migration', substr(explode('/migrations/', $migrationFile)[1], 0, -4))->delete();
    }

    private function publishMigrationsAgain()
    {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SteamaMeter\Providers\SteamaMeterServiceProvider",
            '--tag' => 'migrations',
        ]);
    }

    private function updateDatabase()
    {
        $this->info('Updating database tables\n');
        $this->call('migrate');
    }

    private function publishVueFilesAgain()
    {
        $this->info('Copying vue files\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SteamaMeter\Providers\SteamaMeterServiceProvider",
            '--tag' => 'vue-components',
            '--force' => true,
        ]);
    }

    private function createMenuItems()
    {
        $menuItems = $this->menuItemService->createMenuItems();
        if (array_key_exists('menuItem', $menuItems)) {
            $this->call('menu-items:generate', [
                'menuItem' => $menuItems['menuItem'],
                'subMenuItems' => $menuItems['subMenuItems'],
            ]);
        }
    }
}
