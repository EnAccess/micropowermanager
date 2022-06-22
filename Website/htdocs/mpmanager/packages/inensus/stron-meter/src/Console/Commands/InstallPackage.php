<?php

namespace Inensus\StronMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\StronMeter\Helpers\ApiHelpers;
use Inensus\StronMeter\Services\MenuItemService;
use Inensus\StronMeter\Services\StronCredentialService;

class InstallPackage extends Command
{
    protected $signature = 'stron-meter:install';
    protected $description = 'Install StronMeter Package';

    private $menuItemService;
    private $apiHelpers;
    private $credentialService;
    public function __construct(
        MenuItemService $menuItemService,
        ApiHelpers $apiHelpers,
        StronCredentialService $credentialService
    ) {
        parent::__construct();
        $this->menuItemService = $menuItemService;
        $this->apiHelpers = $apiHelpers;
        $this->credentialService = $credentialService;
    }

    public function handle(): void
    {
        $this->info('Installing Stron Meter Integration Package\n');
        $this->publishMigrations();
        $this->createDatabaseTables();
        $this->publishVueFiles();
        $this->apiHelpers->registerStronMeterManufacturer();
        $this->credentialService->createCredentials();
        $this->createPluginRecord();
        $this->call('routes:generate');
        $this->createMenuItems();
        $this->call('sidebar:generate');
        $this->info('Package installed successfully..');
    }

    private function publishMigrations()
    {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\StronMeter\Providers\StronMeterServiceProvider",
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
            '--provider' => "Inensus\StronMeter\Providers\StronMeterServiceProvider",
            '--tag' => "vue-components"
        ]);
    }

    private function createPluginRecord()
    {
        $this->call('plugin:add', [
            'name' => "StronMeter",
            'composer_name' => "inensus/stron-meter",
            'description' => "Stron Meter integration package for MicroPowerManager",
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
