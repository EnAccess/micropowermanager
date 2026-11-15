<?php

namespace App\Plugins\WhatsappMessaging;

use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Jobs\RetryWhatsappMessage;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Services\WhatsappContactService;
use App\Plugins\WhatsappMessaging\Services\WhatsappCredentialService;
use App\Plugins\WhatsappMessaging\Services\WhatsappMessageService;
use App\Services\PluginsService;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp is tried before the configured SMS gateway, not instead of it: whenever this answers false,
 * core sends by SMS, so companies without the plugin, customers without opt-in and WhatsApp failures all keep getting SMS.
 */
class WhatsappMessagingGateway {
    public function __construct(
        private PluginsService $pluginsService,
        private WhatsappCredentialService $credentialService,
        private WhatsappContactService $contactService,
        private WhatsappMessageService $messageService,
    ) {}

    public function isAvailable(): bool {
        $plugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::WHATSAPP_MESSAGING);

        return $plugin instanceof Plugins && $plugin->status == Plugins::ACTIVE && $this->credentialService->isConfigured();
    }

    /**
     * @param array<string, string> $bodyParserValues
     *
     * @return bool true when Meta accepted the message or a retry is scheduled; false when the caller must send it by SMS
     */
    public function trySend(Sms $sms, string $body, string $receiver, ?string $bodyParser, array $bodyParserValues, bool $isRetry = false): bool {
        $contact = $this->contactService->findReachable($receiver);
        $credential = $this->credentialService->getCredentials();
        if (!$contact instanceof WhatsappContact || !$credential instanceof WhatsappCredential) {
            return false;
        }

        try {
            $this->messageService->send($credential, $sms, $contact, $body, $bodyParser, $bodyParserValues);
        } catch (WhatsappApiException|WhatsappTemplateNotConfiguredException $exception) {
            $retryLater = !$isRetry && $exception instanceof WhatsappApiException && $exception->isTemporary();
            Log::warning('WhatsApp message not sent', ['sms_id' => $sms->id, 'reason' => $exception->getMessage(), 'next_step' => $retryLater ? 'WhatsApp retry' : 'SMS']);
            RetryWhatsappMessage::dispatchIf($retryLater, $sms->id, $body, $receiver, $bodyParser, $bodyParserValues);

            return $retryLater;
        }

        return true;
    }
}
