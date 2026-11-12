<?php

namespace App\Plugins\CalinMeter\Console\Commands;

use App\Plugins\CalinMeter\Services\CalinCredentialService;
use App\Plugins\CalinMeter\Services\ManufacturerService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'calin-meter:install';
    protected $description = 'Install CalinMeter Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
        private CalinCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing CalinMeter Integration Package\n');
        $this->manufacturerService->register();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
