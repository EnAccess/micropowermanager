<?php

namespace Inensus\MesombPaymentProvider\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'mesomb-payment-provider:install';
    protected $description = 'Install MesombPaymentProvider Package';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing MesombPaymentProvider Integration Package\n');
        $this->info('Package installed successfully..');
    }

    private function publishMigrations() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\MesombPaymentProvider\Providers\MesombServiceProvider",
            '--tag' => 'migrations',
        ]);
    }

    private function createDatabaseTables() {
        $this->info('Creating database tables\n');
        $this->call('migrate');
    }

    private function createPluginRecord() {
        $this->call('plugin:add', [
            'name' => 'MesombPaymentProvider',
            'composer_name' => 'inensus/mesomb-payment-provider',
            'description' => 'MesombPaymentProvider integration package for MicroPowerManager',
        ]);
    }
}
