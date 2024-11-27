<?php

namespace Inensus\WaveMoneyPaymentProvider\Console\Commands;

use Illuminate\Console\Command;
use Inensus\WaveMoneyPaymentProvider\Services\WaveMoneyCredentialService;

class InstallPackage extends Command {
    protected $signature = 'wave-money-payment-provider:install';
    protected $description = 'Install WaveMoneyPaymentProvider Package';

    public function __construct(
        private WaveMoneyCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing WaveMoneyPaymentProvider Integration Package\n');
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }

    private function publishMigrations() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\WaveMoneyPaymentProvider\Providers\WaveMoneyPaymentProviderServiceProvider",
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
            '--provider' => "Inensus\WaveMoneyPaymentProvider\Providers\WaveMoneyPaymentProviderServiceProvider",
            '--tag' => 'vue-components',
        ]);
    }

    private function createPluginRecord() {
        $this->call('plugin:add', [
            'name' => 'ViberMessaging',
            'composer_name' => 'inensus/wave-money-payment-provider',
            'description' => 'WaveMoney integration package for MicroPowerManager',
        ]);
    }
}
