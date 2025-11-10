<?php
namespace Inensus\{{Package-Name}}\Console\Commands;

use Illuminate\Console\Command;
use Inensus\{{Package-Name}}\Services\MenuItemService;

class InstallPackage extends Command
{
    protected $signature = '{{package-name}}:install';
    protected $description = 'Install {{Package-Name}} Package';

    private $menuItemService;
    public function __construct(MenuItemService $menuItemService)
    {
        $this->menuItemService=$menuItemService;
    }

    public function handle(): void
    {
        $this->info('Installing {{Package-Name}} Integration Package\n');

        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\{{Package-Name}}\Providers\ServiceProvider",
            '--tag' => "migrations"
        ]);

        $this->info('Creating database tables\n');
        $this->call('migrate');

        $this->info('Copying vue files\n');

        $this->call('vendor:publish', [
            '--provider' => "Inensus\{{Package-Name}}\Providers\ServiceProvider",
            '--tag' => "vue-components"
        ]);

        $this->call('plugin:add', [
            'name' => "{{Package-Name}}",
            'composer_name' => "inensus/{{package-name}}",
            'description' => "{{Package-Name}} integration package for MicroPowerManager",
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