<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Console\Commands;

use App\Plugins\WaveMoneyPaymentProvider\Services\WaveMoneyCredentialService;
use Illuminate\Console\Command;

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
}
