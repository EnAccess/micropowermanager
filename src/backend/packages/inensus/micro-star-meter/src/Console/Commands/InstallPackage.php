<?php

namespace Inensus\MicroStarMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\MicroStarMeter\Services\ManufacturerService;
use Inensus\MicroStarMeter\Services\MicroStarCredentialService;

class InstallPackage extends Command {
    protected $signature = 'micro-star-meter:install';
    protected $description = 'Install MicroStarMeter Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
        private MicroStarCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing MicroStarMeter Integration Package\n');
        $this->manufacturerService->register();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
