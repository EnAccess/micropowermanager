<?php

namespace App\Plugins\AfricasTalking\Console\Commands;

use App\Plugins\AfricasTalking\Services\AfricasTalkingCredentialService;
use Illuminate\Console\Command;

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
