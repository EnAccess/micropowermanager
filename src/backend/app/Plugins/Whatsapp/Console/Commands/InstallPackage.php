<?php

namespace App\Plugins\Whatsapp\Console\Commands;

use App\Plugins\Whatsapp\Services\WhatsappCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'whatsapp:install';
    protected $description = 'Install Whatsapp Package';

    public function handle(WhatsappCredentialService $credentialService): void {
        $this->info('Installing Whatsapp Integration Package');

        // firstOrCreate, so it is safe to run this command repeatedly
        $credentialService->createCredentials();

        $this->info('Package installed successfully..');
    }
}
