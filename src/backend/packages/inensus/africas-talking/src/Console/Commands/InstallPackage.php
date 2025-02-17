<?php

namespace Inensus\AfricasTalking\Console\Commands;

use Illuminate\Console\Command;
use Inensus\AfricasTalking\Services\AfricasTalkingCredentialService;

class InstallPackage extends Command {
    protected $signature = 'africas-talking:install';
    protected $description = 'Install AfricasTalking Package';

    public function __construct(
        private AfricasTalkingCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing AfricasTalking Integration Package\n');
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
