<?php

namespace App\Plugins\TextbeeSmsGateway\Console\Commands;

use App\Plugins\TextbeeSmsGateway\Services\TextbeeCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'textbee-sms-gateway:install';
    protected $description = 'Install TextbeeSmsGateway Package';

    public function __construct(
        private TextbeeCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing TextbeeSmsGateway Integration Package\n');
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
