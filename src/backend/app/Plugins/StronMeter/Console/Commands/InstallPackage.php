<?php

namespace App\Plugins\StronMeter\Console\Commands;

use App\Plugins\StronMeter\Helpers\ApiHelpers;
use App\Plugins\StronMeter\Services\StronCredentialService;
use Illuminate\Console\Command;

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
