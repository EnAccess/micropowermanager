<?php

namespace Inensus\BulkRegistration\Console\Commands;

use Illuminate\Console\Command;
use Inensus\BulkRegistration\Services\MeterTypeService;

class InstallPackage extends Command {
    protected $signature = 'bulk-registration:install';
    protected $description = 'Install Bulk Registration Package';

    public function __construct(
        private MeterTypeService $meterTypeService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing BulkRegistration Integration Package\n');

        $this->meterTypeService->createDefaultMeterTypeIfDoesNotExistAny();

        $this->info('Package installed successfully..');
    }

    private function publishConfigurations() {
        $this->info('Copying configurations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\BulkRegistration\Providers\BulkRegistrationServiceProvider",
            '--tag' => 'configurations',
        ]);
    }

    private function publishMigrations() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\BulkRegistration\Providers\BulkRegistrationServiceProvider",
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
            '--provider' => "Inensus\BulkRegistration\Providers\BulkRegistrationServiceProvider",
            '--tag' => 'vue-components',
            '--force' => true,
        ]);
    }

    private function createPluginRecord() {
        $this->call('plugin:add', [
            'name' => 'BulkRegistration',
            'composer_name' => 'inensus/bulk-registration',
            'description' => 'BulkRegistration integration package for MicroPowerManager',
        ]);
    }
}
