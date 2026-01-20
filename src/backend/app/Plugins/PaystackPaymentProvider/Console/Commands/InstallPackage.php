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
}
