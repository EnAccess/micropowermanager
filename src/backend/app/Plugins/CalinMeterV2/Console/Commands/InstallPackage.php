<?php

namespace App\Plugins\CalinMeterV2\Console\Commands;

use App\Plugins\CalinMeterV2\Services\CalinV2CredentialService;
use App\Plugins\CalinMeterV2\Services\ManufacturerService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'calin-meter-v2:install';
    protected $description = 'Install CalinMeterV2 Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
        private CalinV2CredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing CalinMeterV2 Integration Package\n');
        $this->manufacturerService->register();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
