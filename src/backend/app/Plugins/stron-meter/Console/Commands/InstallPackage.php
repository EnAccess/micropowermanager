<?php

namespace Inensus\StronMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\StronMeter\Helpers\ApiHelpers;
use Inensus\StronMeter\Services\StronCredentialService;

class InstallPackage extends Command {
    protected $signature = 'stron-meter:install';
    protected $description = 'Install StronMeter Package';

    public function __construct(
        private ApiHelpers $apiHelpers,
        private StronCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing Stron Meter Integration Package\n');
        $this->apiHelpers->registerStronMeterManufacturer();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
