<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomMobileMoney\Console\Commands;

use App\Plugins\SafaricomMobileMoney\Services\SafaricomCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'safaricom-mobile-money:install';
    protected $description = 'Install Safaricom Mobile Money Package';

    public function __construct(
        private SafaricomCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): int {
        $this->info('Installing Safaricom Mobile Money Package...');
        $this->createCredentials();
        $this->info('Safaricom Mobile Money Package installed successfully!');

        return 0;
    }

    private function createCredentials(): void {
        if (!$this->credentialService->hasCredentials()) {
            $this->credentialService->createCredentials();
            $this->info('Safaricom credentials created.');
        } else {
            $this->info('Safaricom credentials already exist.');
        }
    }
}
