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

    private function publishMigrations() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\CalinSmartMeter\Providers\CalinSmartMeterServiceProvider",
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
            '--provider' => "Inensus\CalinSmartMeter\Providers\CalinSmartMeterServiceProvider",
            '--tag' => 'vue-components',
        ]);
    }

    private function createPluginRecord() {
        $this->call('plugin:add', [
            'name' => 'CalinSmartMeter',
            'composer_name' => 'inensus/calin-smart-meter',
            'description' => 'CalinSmartMeter integration package for MicroPowerManager',
        ]);
    }
}
