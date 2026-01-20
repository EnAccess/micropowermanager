<?php

namespace App\Plugins\GomeLongMeter\Console\Commands;

use App\Plugins\GomeLongMeter\Services\GomeLongCredentialService;
use App\Plugins\GomeLongMeter\Services\ManufacturerService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'gome-long-meter:install';
    protected $description = 'Install GomeLongMeter Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
        private GomeLongCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing GomeLongMeter Integration Package\n');

        $this->manufacturerService->register();
        $this->credentialService->createCredentials();

        $this->info('Package installed successfully..');
    }
}
