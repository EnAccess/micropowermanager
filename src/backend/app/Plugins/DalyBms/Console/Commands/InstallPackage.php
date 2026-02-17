<?php

namespace App\Plugins\DalyBms\Console\Commands;

use App\Plugins\DalyBms\Services\DalyBmsCredentialService;
use App\Plugins\DalyBms\Services\ManufacturerService;
use Illuminate\Console\Command;

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
