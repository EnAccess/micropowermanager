<?php

namespace App\Plugins\AirtelPaymentProvider\Console\Commands;

use App\Plugins\AirtelPaymentProvider\Services\AirtelCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'airtel-payment-provider:install';
    protected $description = 'Install AirtelPaymentProvider Package';

    public function __construct(
        private AirtelCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing AirtelPaymentProvider Integration Package\n');
        $this->credentialService->getCredentials();
        $this->info('Package installed successfully..');
    }
}
