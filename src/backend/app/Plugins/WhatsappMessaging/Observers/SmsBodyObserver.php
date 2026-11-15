<?php

namespace App\Plugins\WhatsappMessaging\Observers;

use App\Models\SmsBody;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Services\WhatsappCredentialService;
use App\Plugins\WhatsappMessaging\Services\WhatsappTemplateService;
use App\Plugins\WhatsappMessaging\WhatsappMessagingGateway;
use Illuminate\Support\Facades\Log;

class SmsBodyObserver {
    public function __construct(
        private WhatsappMessagingGateway $gateway,
        private WhatsappCredentialService $credentialService,
        private WhatsappTemplateService $templateService,
    ) {}

    // New wording means a new template name, which Meta must approve before WhatsApp can send it; until then that message goes by SMS.
    public function updated(SmsBody $smsBody): void {
        if (!$smsBody->wasChanged('body') || !$this->gateway->isAvailable()) {
            return;
        }

        $credential = $this->credentialService->getCredentials();
        $errors = $credential instanceof WhatsappCredential ? $this->templateService->sync($credential) : [];
        if ($errors !== []) {
            Log::warning('WhatsApp refused templates after an SMS text change; those messages go by SMS', ['errors' => $errors]);
        }
    }
}
