<?php

namespace App\Plugins\AirtelSmsGateway\Console\Commands;

use App\Plugins\AirtelSmsGateway\Services\AirtelSmsCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'airtel-sms-gateway:install';
    protected $description = 'Install AirtelSmsGateway Package';

    public function __construct(
        private AirtelSmsCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing AirtelSmsGateway Integration Package\n');
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
