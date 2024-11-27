<?php

namespace Inensus\MicroStarMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\MicroStarMeter\Services\ManufacturerService;
use Inensus\MicroStarMeter\Services\MicroStarCredentialService;

class InstallPackage extends Command {
    protected $signature = 'micro-star-meter:install';
    protected $description = 'Install MicroStarMeter Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
        private MicroStarCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing MicroStarMeter Integration Package\n');
        $this->manufacturerService->register();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }

    private function publishMigrations() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\MicroStarMeter\Providers\MicroStarMeterServiceProvider",
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
            '--provider' => "Inensus\MicroStarMeter\Providers\MicroStarMeterServiceProvider",
            '--tag' => 'vue-components',
        ]);
    }

    private function createPluginRecord() {
        $this->call('plugin:add', [
            'name' => 'MicroStarMeter',
            'composer_name' => 'inensus/micro-star-meter',
            'description' => 'MicroStarMeter integration package for MicroPowerManager',
        ]);
    }
}
