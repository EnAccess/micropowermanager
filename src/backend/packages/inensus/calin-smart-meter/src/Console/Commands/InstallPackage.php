<?php

namespace Inensus\CalinSmartMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\CalinSmartMeter\Helpers\ApiHelpers;
use Inensus\CalinSmartMeter\Services\CalinSmartCredentialService;

class InstallPackage extends Command {
    protected $signature = 'calin-smart-meter:install';
    protected $description = 'Install CalinSmartMeter Package';

    public function __construct(
        private CalinSmartCredentialService $credentialService,
        private ApiHelpers $apiHelpers,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing CalinSmartMeter Integration Package\n');
        $this->apiHelpers->registerCalinMeterManufacturer();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
