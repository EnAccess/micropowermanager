<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Console\Commands;

use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'flutterwave-payment-provider:install';
    protected $description = 'Install Flutterwave Payment Provider Package';

    public function __construct(
        private FlutterwaveCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): int {
        $this->info('Installing Flutterwave Payment Provider Package...');
        $this->createCredentials();
        $this->info('Flutterwave Payment Provider Package installed successfully!');

        return 0;
    }

    private function createCredentials(): void {
        if (!$this->credentialService->hasCredentials()) {
            $this->credentialService->createCredentials();
            $this->info('Flutterwave credentials created.');
        } else {
            $this->info('Flutterwave credentials already exist.');
        }
    }
}
