<?php
namespace Inensus\SunKingMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\SunKingMeter\Services\SunKingCredentialService;
use Inensus\SunKingMeter\Services\ManufacturerService;
use Inensus\SunKingMeter\Services\MenuItemService;

class InstallPackage extends Command
{
    protected $signature = 'sun-king-meter:install';
    protected $description = 'Install SunKingMeter Package';

    public function __construct(
        private MenuItemService $menuItemService,
        private ManufacturerService $manufacturerService,
        private SunKingCredentialService $credentialService
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Installing SunKingMeter Integration Package\n');

        $this->manufacturerService->register();
        $this->credentialService->createCredentials();

        $this->info('Package installed successfully..');
    }

    private function publishMigrations()
    {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SunKingMeter\Providers\SunKingMeterServiceProvider",
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
            '--provider' => "Inensus\SunKingMeter\Providers\SunKingMeterServiceProvider",
            '--tag' => "vue-components"
        ]);
    }

    private function createPluginRecord()
    {
        $this->call('plugin:add', [
            'name' => "SunKingMeter",
            'composer_name' => "inensus/sun-king-meter",
            'description' => "SunKingMeter integration package for MicroPowerManager",
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