<?php

namespace App\Plugins\CalinMeter\Console\Commands;

use App\Plugins\CalinMeter\Helpers\ApiHelpers;
use App\Plugins\CalinMeter\Services\CalinCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'calin-meter:install';
    protected $description = 'Install CalinMeter Package';

    public function __construct(
        private ApiHelpers $apiHelpers,
        private CalinCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing CalinMeter Integration Package\n');
        $this->apiHelpers->registerCalinMeterManufacturer();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
