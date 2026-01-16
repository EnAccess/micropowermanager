<?php

namespace App\Plugins\AngazaSHS\Console\Commands;

use App\Plugins\AngazaSHS\Services\AngazaCredentialService;
use App\Plugins\AngazaSHS\Services\ManufacturerService;
use Illuminate\Console\Command;

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
