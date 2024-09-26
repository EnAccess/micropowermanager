<?php

namespace Inensus\GomeLongMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\GomeLongMeter\Services\GomeLongCredentialService;
use Inensus\GomeLongMeter\Services\ManufacturerService;
use Inensus\GomeLongMeter\Services\MenuItemService;

class InstallPackage extends Command
{
    protected $signature = 'gome-long-meter:install';
    protected $description = 'Install GomeLongMeter Package';

    public function __construct(
        private MenuItemService $menuItemService,
        private ManufacturerService $manufacturerService,
        private GomeLongCredentialService $credentialService
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Installing GomeLongMeter Integration Package\n');

        $this->manufacturerService->register();
        $this->credentialService->createCredentials();

        $this->info('Package installed successfully..');
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
