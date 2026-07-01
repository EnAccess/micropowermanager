<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomKePaymentProvider\Console\Commands;

use App\Plugins\SafaricomKePaymentProvider\Services\SafaricomCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'safaricom-ke-payment-provider:install';
    protected $description = 'Install Safaricom KE Package';

    public function __construct(
        private SafaricomCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): int {
        $this->info('Installing Safaricom KE Package...');
        $this->createCredentials();
        $this->info('Safaricom KE Package installed successfully!');

        return 0;
    }

    private function createCredentials(): void {
        if (!$this->credentialService->hasCredentials()) {
            $this->credentialService->createCredentials();
            $this->info('Safaricom credentials created.');
        } else {
            $this->info('Safaricom credentials already exist.');
        }
    }
}
