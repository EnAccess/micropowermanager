<?php

namespace Inensus\ViberMessaging\Console\Commands;

use Illuminate\Console\Command;
use Inensus\ViberMessaging\Services\MenuItemService;
use Inensus\ViberMessaging\Services\ViberCredentialService;

class InstallPackage extends Command
{
    protected $signature = 'viber-messaging:install';
    protected $description = 'Install ViberMessaging Package';

    public function __construct(
        private MenuItemService $menuItemService,
        private ViberCredentialService $credentialService
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Installing ViberMessaging Integration Package\n');
        $this->createMenuItems();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }

    private function publishMigrations()
    {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\ViberMessaging\Providers\ViberMessagingServiceProvider",
            '--tag' => 'migrations',
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
            '--provider' => "Inensus\ViberMessaging\Providers\ViberMessagingServiceProvider",
            '--tag' => 'vue-components',
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
