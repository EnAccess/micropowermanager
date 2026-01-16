<?php

namespace App\Plugins\KelinMeter\Console\Commands;

use App\Plugins\KelinMeter\Helpers\ApiHelpers;
use App\Plugins\KelinMeter\Services\KelinCredentialService;
use App\Plugins\KelinMeter\Services\PackageInstallationService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'kelin-meter:install';
    protected $description = 'Install KelinMeters Package';

    public function __construct(
        private ApiHelpers $apiHelpers,
        private KelinCredentialService $credentialService,
        private PackageInstallationService $packageInstallationService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing KelinMeters Integration Package\n');
        $this->packageInstallationService->createDefaultSettingRecords();
        $this->apiHelpers->registerMeterManufacturer();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
