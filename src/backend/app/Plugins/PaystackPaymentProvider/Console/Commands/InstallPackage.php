<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Console\Commands;

use App\Plugins\PaystackPaymentProvider\Providers\PaystackPaymentProviderServiceProvider;
use App\Plugins\PaystackPaymentProvider\Services\PaystackCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'paystack-payment-provider:install';
    protected $description = 'Install Paystack Payment Provider Package';

    public function __construct(
        private PaystackCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): int {
        $this->info('Installing Paystack Payment Provider Package...');
        $this->createCredentials();
        $this->info('Paystack Payment Provider Package installed successfully!');

        return 0;
    }

    private function createCredentials(): void {
        if (!$this->credentialService->hasCredentials()) {
            $this->credentialService->createCredentials();
            $this->info('Paystack credentials created.');
        } else {
            $this->info('Paystack credentials already exist.');
        }
    }

    /** @phpstan-ignore-next-line */
    private function publishMigrations(): void {
        $this->call('vendor:publish', [
            '--provider' => PaystackPaymentProviderServiceProvider::class,
            '--tag' => 'migrations',
        ]);
    }

    /** @phpstan-ignore-next-line */
    private function createDatabaseTables(): void {
        $this->call('migrate', [
            '--path' => 'vendor/inensus/paystack-payment-provider/database/migrations',
        ]);
    }

    /** @phpstan-ignore-next-line */
    private function createPluginRecord(): void {
        $this->call('plugin:add', [
            'name' => 'Paystack Payment Provider',
            'composer_name' => 'inensus/paystack-payment-provider',
            'description' => 'Paystack Payment Provider integration for MicroPowerManager',
        ]);
    }
}
