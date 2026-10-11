<?php

namespace App\Plugins\WhatsappMessaging\Console\Commands;

use App\Plugins\WhatsappMessaging\Services\WhatsappCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'whatsapp-messaging:install';
    protected $description = 'Install WhatsappMessaging Package';

    public function __construct(
        private WhatsappCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing WhatsappMessaging Integration Package\n');
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
