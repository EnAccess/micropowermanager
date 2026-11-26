<?php

namespace App\Plugins\EgoSmsGateway\Console\Commands;

use App\Plugins\EgoSmsGateway\Services\EgoSmsCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'ego-sms-gateway:install';
    protected $description = 'Install EgoSmsGateway Package';

    public function __construct(
        private EgoSmsCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing EgoSmsGateway Integration Package\n');
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
