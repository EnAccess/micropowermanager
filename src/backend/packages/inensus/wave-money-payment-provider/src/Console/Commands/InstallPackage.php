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
}
