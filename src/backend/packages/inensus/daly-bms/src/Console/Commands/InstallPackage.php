<?php

namespace Inensus\DalyBms\Console\Commands;

use Illuminate\Console\Command;
use Inensus\DalyBms\Services\DalyBmsCredentialService;
use Inensus\DalyBms\Services\ManufacturerService;

class InstallPackage extends Command {
    protected $signature = 'daly-bms:install';
    protected $description = 'Install DalyBms Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
        private DalyBmsCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing DalyBms Integration Package\n');
        $this->manufacturerService->register();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
