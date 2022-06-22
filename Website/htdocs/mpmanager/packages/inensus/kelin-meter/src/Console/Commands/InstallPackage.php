<?php

namespace Inensus\KelinMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\KelinMeter\Helpers\ApiHelpers;
use Inensus\KelinMeter\Services\KelinCredentialService;
use Inensus\KelinMeter\Services\MenuItemService;
use Inensus\KelinMeter\Services\PackageInstallationService;


class InstallPackage extends Command
{
    protected $signature = 'kelin-meter:install';
    protected $description = 'Install KelinMeters Package';

    private $menuItemService;
    private $apiHelpers;
    private $credentialService;
    private $packageInstallationService;

    public function __construct(
        MenuItemService $menuItemService,
        ApiHelpers $apiHelpers,
        KelinCredentialService $credentialService,
        PackageInstallationService $packageInstallationService
    ) {
        parent::__construct();
        $this->menuItemService = $menuItemService;
        $this->apiHelpers = $apiHelpers;
        $this->credentialService = $credentialService;
        $this->packageInstallationService = $packageInstallationService;
    }

    public function handle(): void
    {
        $this->info('Installing KelinMeters Integration Package\n');

        $this->publishMigrations();
        $this->publishConfigurations();
        $this->createDatabaseTables();
        $this->packageInstallationService->createDefaultSettingRecords();
        $this->publishVueFiles();
        $this->apiHelpers->registerMeterManufacturer();
        $this->credentialService->createCredentials();
        $this->createPluginRecord();
        $this->call('routes:generate');
        $menuItems = $this->menuItemService->createMenuItems();
        $this->call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);
        $this->call('sidebar:generate');
        $this->info('Package installed successfully..');
    }

    private function publishConfigurations()
    {
        $this->info('Copying configurations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\KelinMeter\Providers\KelinMeterServiceProvider",
            '--tag' => "configurations",
        ]);
    }

    private function publishMigrations()
    {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\KelinMeter\Providers\KelinMeterServiceProvider",
            '--tag' => "migrations"
        ]);
    }

    private function createDatabaseTables()
    {
        $this->info('Creating database tables\n');
        $this->call('migrate');
    }

    private function publishVueFiles()
    {
        $this->info('Copying vue files\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\KelinMeter\Providers\KelinMeterServiceProvider",
            '--tag' => "vue-components"
        ]);
    }

    private function createPluginRecord()
    {
        $this->call('plugin:add', [
            'name' => "KelinMeters",
            'composer_name' => "inensus/kelin-meter",
            'description' => "KelinMeters integration package for MicroPowerManager",
        ]);
    }
}