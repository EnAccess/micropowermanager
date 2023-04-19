<?php

namespace Inensus\WavecomPaymentProvider\Console\Commands;

use Illuminate\Console\Command;
use Inensus\WavecomPaymentProvider\Services\MenuItemService;

class InstallPackage extends Command
{
    protected $signature = 'wavecom-payment-provider:install';
    protected $description = 'Install wavecom-money-payment-provider Package';

    private $menuItemService;
    public function __construct(MenuItemService $menuItemService)
    {
        $this->menuItemService = $menuItemService;
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Installing WavecomPaymentProvider Integration Package\n');

        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\WavecomPaymentProvider\Providers\ServiceProvider",
            '--tag' => "migrations"
        ]);

        $this->info('Creating database tables\n');
        $this->call('migrate');

        $this->info('Copying vue files\n');

        $this->call('vendor:publish', [
            '--provider' => "Inensus\\WavecomPaymentProvider\\Providers\ServiceProvider",
            '--tag' => "vue-components"
        ]);

        $this->call('plugin:add', [
            'name' => "WavecomPaymentProvider",
            'composer_name' => "inensus/WavecomPaymentProvider",
            'description' => "WavecomPaymentProvider integration package for MicroPowerManager",
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
