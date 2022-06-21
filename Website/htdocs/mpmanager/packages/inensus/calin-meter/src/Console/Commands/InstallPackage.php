<?php

namespace Inensus\CalinMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\CalinMeter\Helpers\ApiHelpers;
use Inensus\CalinMeter\Services\MenuItemService;
use Inensus\CalinMeter\Services\CalinCredentialService;

class InstallPackage extends Command
{
    protected $signature = 'calin-meter:install';
    protected $description = 'Install CalinMeter Package';

    private $menuItemService;
    private $apiHelpers;
    private $credentialService;
    public function __construct(
        MenuItemService $menuItemService,
        ApiHelpers $apiHelpers,
        CalinCredentialService $credentialService
    ) {
        parent::__construct();
        $this->menuItemService = $menuItemService;
        $this->apiHelpers = $apiHelpers;
        $this->credentialService = $credentialService;
    }

    public function handle(): void
    {
        $this->info('Installing CalinMeter Integration Package\n');

        $this->publishMigrations();
        $this->createDatabaseTables();
        $this->publishVueFiles();
        $this->apiHelpers->registerCalinMeterManufacturer();
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
            '--provider' => "qq",
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
            '--provider' => "Inensus\CalinMeter\Providers\CalinMeterServiceProvider",
            '--tag' => "vue-components"
        ]);
    }

    private function createPluginRecord()
    {
        $this->call('plugin:add', [
            'name' => "CalinMeter",
            'composer_name' => "inensus/calin-meter",
            'description' => "CalinMeter integration package for MicroPowerManager",
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