<?php

namespace Inensus\CalinMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\CalinMeter\Helpers\ApiHelpers;
use Inensus\CalinMeter\Services\CalinCredentialService;

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

    private function publishMigrations() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => 'qq',
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
            '--provider' => "Inensus\CalinMeter\Providers\CalinMeterServiceProvider",
            '--tag' => 'vue-components',
        ]);
    }

    private function createPluginRecord() {
        $this->call('plugin:add', [
            'name' => 'CalinMeter',
            'composer_name' => 'inensus/calin-meter',
            'description' => 'CalinMeter integration package for MicroPowerManager',
        ]);
    }
}
