<?php

namespace Inensus\ChintMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\ChintMeter\Services\ChintCredentialService;
use Inensus\ChintMeter\Services\ManufacturerService;

class InstallPackage extends Command {
    protected $signature = 'chint-meter:install';
    protected $description = 'Install ChintMeter Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
        private ChintCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing ChintMeter Integration Package\n');

        $this->manufacturerService->register();
        $this->credentialService->createCredentials();

        $this->info('Package installed successfully..');
    }
}
