<?php
namespace Inensus\AngazaSHS\Console\Commands;

use Illuminate\Console\Command;
use Inensus\AngazaSHS\Services\AngazaCredentialService;
use Inensus\AngazaSHS\Services\ManufacturerService;
use Inensus\AngazaSHS\Services\MenuItemService;

class InstallPackage extends Command
{
    protected $signature = 'angaza-shs:install';
    protected $description = 'Install AngazaSHS Package';

    public function __construct(
        private MenuItemService $menuItemService,
        private ManufacturerService $manufacturerService,
        private AngazaCredentialService $credentialService
    ) {
        parent::__construct();
    }
    public function handle(): void
    {
        $this->info('Installing AngazaSHS Integration Package\n');
        $this->manufacturerService->register();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }

}