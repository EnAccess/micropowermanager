<?php

namespace Inensus\ViberMessaging\Console\Commands;

use Illuminate\Console\Command;
use Inensus\ViberMessaging\Services\ViberCredentialService;

class InstallPackage extends Command {
    protected $signature = 'viber-messaging:install';
    protected $description = 'Install ViberMessaging Package';

    public function __construct(
        private ViberCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing ViberMessaging Integration Package\n');
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
