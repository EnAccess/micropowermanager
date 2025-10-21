<?php

namespace Inensus\KelinMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\KelinMeter\Helpers\ApiHelpers;
use Inensus\KelinMeter\Services\KelinCredentialService;
use Inensus\KelinMeter\Services\PackageInstallationService;

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
