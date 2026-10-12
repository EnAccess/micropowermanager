<?php

namespace App\Plugins\WhatsApp\Console\Commands;

use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'whatsapp:install';
    protected $description = 'Install WhatsApp Integration Package';

    public function __construct(
        private WhatsAppCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing WhatsApp Integration Package...');
        $this->credentialService->createCredentials();
        $this->info('WhatsApp Package installed successfully.');
    }
}