<?php

namespace App\Plugins\WhatsappMessaging;

use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Services\WhatsappContactService;
use App\Plugins\WhatsappMessaging\Services\WhatsappCredentialService;
use App\Plugins\WhatsappMessaging\Services\WhatsappMessageService;
use App\Services\PluginsService;

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
    public function canSendFreeText(string $receiver): bool {
        $contact = $this->contactService->findReachable($receiver);

        return $this->isAvailable() && $contact instanceof WhatsappContact && $contact->isCustomerServiceWindowOpen();
    }

    /**
     * @param array<string, string> $values the body parser's values, or the operator's values for $templateId
     *
     * @return bool true when Meta accepted the message; false when the customer has not opted in, so WhatsApp was not tried
     *
     * @throws WhatsappApiException|WhatsappTemplateNotConfiguredException when it was tried and failed; the attempt is logged either way
     */
    public function send(Sms $sms, string $receiver, ?string $bodyParserReference, array $values, ?string $templateId): bool {
        $contact = $this->contactService->findReachable($receiver);
        $credential = $this->credentialService->getCredentials();
        if (!$contact instanceof WhatsappContact || !$credential instanceof WhatsappCredential) {
            return false;
        }

        $this->messageService->send($credential, $sms, $contact, $bodyParserReference, $values, $templateId);

        return true;
    }

    /**
     * The retry sends exactly what the failed attempt sent, because the body parser values are gone by the time it runs.
     *
     * @return bool false when the customer opted out or the plugin was turned off since, so WhatsApp was not tried
     *
     * @throws WhatsappApiException|WhatsappTemplateNotConfiguredException
     */
    public function resend(Sms $sms): bool {
        $contact = $this->contactService->findReachable($sms->receiver);
        $credential = $this->credentialService->getCredentials();
        if (!$this->isAvailable() || !$contact instanceof WhatsappContact || !$credential instanceof WhatsappCredential) {
            return false;
        }

        $this->messageService->resend($credential, $sms, $contact);

        return true;
    }
}
