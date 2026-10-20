<?php

namespace App\Plugins\WhatsApp\Console\Commands;

use App\Plugins\WhatsApp\Models\WhatsAppTemplate;
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
        $this->seedDefaultTemplates();
        $this->info('WhatsApp Package installed successfully.');
    }

    private function seedDefaultTemplates(): void {
        foreach (WhatsAppTemplate::DEFAULT_BODIES as $type => $body) {
            WhatsAppTemplate::query()->firstOrCreate(
                ['type' => $type],
                [
                    'name' => WhatsAppTemplate::TYPES[$type],
                    'language' => 'en',
                    'body' => $body,
                    'status' => WhatsAppTemplate::STATUS_ACTIVE,
                ],
            );
        }
    }
}
