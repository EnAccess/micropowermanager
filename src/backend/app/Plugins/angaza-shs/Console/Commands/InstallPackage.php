<?php

namespace Inensus\AngazaSHS\Console\Commands;

use Illuminate\Console\Command;
use Inensus\AngazaSHS\Services\AngazaCredentialService;
use Inensus\AngazaSHS\Services\ManufacturerService;

class InstallPackage extends Command {
    protected $signature = 'angaza-shs:install';
    protected $description = 'Install AngazaSHS Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
        private AngazaCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing AngazaSHS Integration Package\n');
        $this->manufacturerService->register();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
