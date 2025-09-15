<?php
namespace Inensus\Prospect\Console\Commands;

use Illuminate\Console\Command;
use Inensus\Prospect\Services\MenuItemService;

class InstallPackage extends Command
{
    protected $signature = 'prospect:install';
    protected $description = 'Install Prospect Package';

    private $menuItemService;
    public function __construct(MenuItemService $menuItemService)
    {
        $this->menuItemService=$menuItemService;
    }

    public function handle(): void
    {
        $this->info('Installing Prospect Integration Package\n');

        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\Prospect\Providers\ServiceProvider",
            '--tag' => "migrations"
        ]);

        $this->info('Creating database tables\n');
        $this->call('migrate');

        $this->info('Copying vue files\n');

        $this->call('vendor:publish', [
            '--provider' => "Inensus\Prospect\Providers\ServiceProvider",
            '--tag' => "vue-components"
        ]);

        $this->call('plugin:add', [
            'name' => "Prospect",
            'composer_name' => "inensus/prospect",
            'description' => "Prospect integration package for MicroPowerManager",
        ]);
        $this->call('routes:generate');

        $menuItems = $this->menuItemService->createMenuItems();
        $this->call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);

        $this->call('sidebar:generate');

        $this->info('Package installed successfully..');
    }
}