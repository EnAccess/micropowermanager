<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\MainSettings;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;

class WhatsappMessageComposer {
    // Meta allows free text for 24 hours after the customer's last message; the 10 minute margin covers time spent in the queue.
    private const int CUSTOMER_SERVICE_WINDOW_MINUTES = 23 * 60 + 50;

    /**
     * MPM company language => Meta template language code (https://developers.facebook.com/docs/whatsapp/business-management-api/message-templates/supported-languages).
     * Meta treats pt_PT and pt_BR as different templates; change "pt" to pt_BR if the approved templates are Brazilian Portuguese.
     * Burmese ("bu") is missing because Meta does not support it, so those companies get English templates.
     */
    private const array META_LANGUAGE_CODES = [
        'en' => 'en',
        'fr' => 'fr',
        'pt' => 'pt_PT',
        'ar' => 'ar',
        'sw' => 'sw',
    ];

    public function __construct(
        private WhatsappTemplateService $templateService,
        private MainSettings $mainSettings,
    ) {}

    /**
     * Decides between free text and a template before sending, because a free-text message outside the window is rejected by Meta (131047).
     *
     * @param string                $body       the rendered SMS text
     * @param string|null           $bodyParser the SmsBodyParser class name that rendered $body
     * @param array<string, string> $values     the parser's variable values, keyed by variable name
     *
     * @throws WhatsappTemplateNotConfiguredException
     */
    public function compose(string $body, ?string $bodyParser, array $values, WhatsappContact $contact): WhatsappOutboundMessage {
        if ($this->isCustomerServiceWindowOpen($contact)) {
            return new WhatsappOutboundMessage($contact->phone, $body);
        }

        if ($bodyParser === null) {
            throw new WhatsappTemplateNotConfiguredException('This message has no WhatsApp template because it was not built from an SMS body parser.');
        }

        $template = $this->findTemplate($bodyParser);
        $parameters = [];

        foreach ($template->parameters as $variable) {
            if (!array_key_exists($variable, $values)) {
                throw new WhatsappTemplateNotConfiguredException(sprintf('WhatsApp template "%s" expects variable "%s", which %s does not provide.', $template->template_name, $variable, $bodyParser));
            }

            $parameters[] = $this->sanitiseParameter($values[$variable]);
        }

        return new WhatsappOutboundMessage($contact->phone, $body, $template->template_name, $template->language_code, $parameters);
    }

    /**
     * Translated templates need their own Meta approval, so a company language without an approved mapping falls back to English.
     *
     * @throws WhatsappTemplateNotConfiguredException
     */
    private function findTemplate(string $bodyParser): WhatsappTemplate {
        $companyLanguage = $this->mainSettings->newQuery()->value('language') ?? WhatsappTemplateService::DEFAULT_LANGUAGE;
        $languages = array_unique([
            self::META_LANGUAGE_CODES[$companyLanguage] ?? WhatsappTemplateService::DEFAULT_LANGUAGE,
            WhatsappTemplateService::DEFAULT_LANGUAGE,
        ]);

        foreach ($languages as $language) {
            $template = $this->templateService->findActive($bodyParser, $language);

            if ($template instanceof WhatsappTemplate) {
                return $template;
            }
        }

        throw new WhatsappTemplateNotConfiguredException(sprintf('No active WhatsApp template is mapped to %s in language "%s".', $bodyParser, implode('" or "', $languages)));
    }

    private function isCustomerServiceWindowOpen(WhatsappContact $contact): bool {
        return $contact->last_inbound_at?->isAfter(now()->subMinutes(self::CUSTOMER_SERVICE_WINDOW_MINUTES)) ?? false;
    }

    // Meta rejects template parameters that are empty or contain newlines, tabs or long runs of spaces.
    private function sanitiseParameter(string $value): string {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        return $value === '' ? '-' : $value;
    }
}
