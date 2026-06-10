<?php

namespace App\Plugins\VodacomMzPaymentProvider\Console\Commands;

use App\Plugins\VodacomMzPaymentProvider\Services\VodacomMzCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'vodacom-mz-payment-provider:install';
    protected $description = 'Install VodacomMzPaymentProvider Package';

    public function __construct(
        private VodacomMzCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing VodacomMzPaymentProvider Integration Package\n');

        $this->credentialService->getCredentials();

        $this->info('Package installed successfully..');
    }
}
