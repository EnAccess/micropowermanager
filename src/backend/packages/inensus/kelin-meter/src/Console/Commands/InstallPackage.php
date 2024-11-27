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

    private function publishConfigurations() {
        $this->info('Copying configurations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\KelinMeter\Providers\KelinMeterServiceProvider",
            '--tag' => 'configurations',
        ]);
    }

    private function publishMigrations() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\KelinMeter\Providers\KelinMeterServiceProvider",
            '--tag' => 'migrations',
        ]);
    }

    private function createDatabaseTables() {
        $this->info('Creating database tables\n');
        $this->call('migrate');
    }

    private function publishVueFiles() {
        $this->info('Copying vue files\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\KelinMeter\Providers\KelinMeterServiceProvider",
            '--tag' => 'vue-components',
        ]);
    }

    private function createPluginRecord() {
        $this->call('plugin:add', [
            'name' => 'KelinMeters',
            'composer_name' => 'inensus/kelin-meter',
            'description' => 'KelinMeters integration package for MicroPowerManager',
        ]);
    }
}
