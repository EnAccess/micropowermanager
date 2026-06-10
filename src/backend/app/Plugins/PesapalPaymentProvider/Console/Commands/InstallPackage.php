<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Console\Commands;

use App\Plugins\PesapalPaymentProvider\Services\PesapalCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'pesapal-payment-provider:install';
    protected $description = 'Install Pesapal Payment Provider Package';

    public function __construct(
        private PesapalCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): int {
        $this->info('Installing Pesapal Payment Provider Package...');
        $this->createCredentials();
        $this->info('Pesapal Payment Provider Package installed successfully!');

        return 0;
    }

    private function createCredentials(): void {
        if (!$this->credentialService->hasCredentials()) {
            $this->credentialService->createCredentials();
            $this->info('Pesapal credentials created.');
        } else {
            $this->info('Pesapal credentials already exist.');
        }
    }
}
