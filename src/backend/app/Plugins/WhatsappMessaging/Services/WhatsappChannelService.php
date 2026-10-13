<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappMessageNotSentException;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use App\Plugins\WhatsappMessaging\WhatsappMessagingGateway;
use App\Services\PluginsService;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp is a channel tried before the configured SMS gateway, not an SMS gateway itself.
 * Core sending code asks this service first and uses the SMS gateway whenever it answers false,
 * so companies without the plugin, customers without opt-in and WhatsApp failures all keep getting SMS.
 */
class WhatsappChannelService {
    public function __construct(
        private PluginsService $pluginsService,
        private WhatsappCredentialService $credentialService,
        private WhatsappContactService $contactService,
        private WhatsappMessageComposer $messageComposer,
        private WhatsappMessagingGateway $gateway,
        private WhatsappMessageService $whatsappMessageService,
    ) {}

    public function isAvailable(): bool {
        $plugin = $this->pluginsService->getByMpmPluginId(MpmPlugin::WHATSAPP_MESSAGING);

        return $plugin instanceof Plugins
            && $plugin->status == Plugins::ACTIVE
            && $this->credentialService->isConfigured();
    }

    /**
     * @param array<string, string> $bodyParserValues
     *
     * @return bool true when Meta accepted the message; false when the caller must send it by SMS
     *
     * @throws WhatsappMessageNotSentException|WhatsappTemplateNotConfiguredException when WhatsApp fails and SMS fallback is disabled
     */
    public function trySend(Sms $sms, string $body, string $receiver, ?string $bodyParser, array $bodyParserValues): bool {
        $contact = $this->contactService->findReachable($receiver);

        if (!$contact instanceof WhatsappContact) {
            return false;
        }

        try {
            $message = $this->messageComposer->compose($body, $bodyParser, $bodyParserValues, $contact);
        } catch (WhatsappTemplateNotConfiguredException $exception) {
            // The gateway records API failures itself; this one happens before the gateway is reached.
            $this->whatsappMessageService->create([
                'sms_id' => $sms->id,
                'whatsapp_contact_id' => $contact->id,
                'message_type' => WhatsappMessage::MESSAGE_TYPE_TEMPLATE,
                'status' => WhatsappMessage::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'failed_at' => now(),
            ]);

            return $this->fallBackOrRethrow($sms, $exception);
        }

        try {
            $this->gateway->sendSms($message, $sms, $contact);
        } catch (WhatsappMessageNotSentException $exception) {
            return $this->fallBackOrRethrow($sms, $exception);
        }

        return true;
    }

    /**
     * @throws WhatsappMessageNotSentException|WhatsappTemplateNotConfiguredException
     */
    private function fallBackOrRethrow(Sms $sms, WhatsappMessageNotSentException|WhatsappTemplateNotConfiguredException $exception): bool {
        if (!$this->credentialService->isSmsFallbackEnabled()) {
            throw $exception;
        }

        Log::warning('WhatsApp message not sent, falling back to SMS', [
            'sms_id' => $sms->id,
            'reason' => $exception->getMessage(),
        ]);

        return false;
    }
}
