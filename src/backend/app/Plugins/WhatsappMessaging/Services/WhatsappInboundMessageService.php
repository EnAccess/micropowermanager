<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\MainSettings;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use Illuminate\Support\Carbon;

class WhatsappInboundMessageService {
    public const array OPT_IN_KEYWORDS = ['JOIN', 'START', 'YES'];
    public const array OPT_OUT_KEYWORDS = ['STOP', 'UNSUBSCRIBE'];

    private const string MESSAGE_TYPE_TEXT = 'text';

    public function __construct(
        private WhatsappContactService $contactService,
        private MainSettings $mainSettings,
    ) {}

    /**
     * Processes one entry of a webhook's `messages` array.
     *
     * @param array{from: string, timestamp: string, type: string, text?: array{body?: string}} $message
     *
     * @return string|null confirmation to send back as free text, or null when no reply is due
     */
    public function handle(array $message): ?string {
        $contact = $this->contactService->findOrCreateByPhone('+'.ltrim($message['from'], '+'));
        $this->contactService->recordInbound($contact, $message['from'], Carbon::createFromTimestamp((int) $message['timestamp']));

        if ($message['type'] !== self::MESSAGE_TYPE_TEXT) {
            return null;
        }

        $body = $message['text']['body'] ?? '';

        return match ($this->matchKeyword($body)) {
            WhatsappConsentLog::ACTION_OPT_IN => $this->contactService->optIn($contact, WhatsappConsentLog::SOURCE_KEYWORD, null, $body)
                ? sprintf('You are now subscribed to WhatsApp messages from %s. Reply STOP to unsubscribe.', $this->companyName())
                : null,
            WhatsappConsentLog::ACTION_OPT_OUT => $this->contactService->optOut($contact, WhatsappConsentLog::SOURCE_KEYWORD, null, $body)
                ? sprintf('You will no longer receive WhatsApp messages from %s. Reply JOIN to subscribe again.', $this->companyName())
                : null,
            default => null,
        };
    }

    /**
     * Matches the whole normalised message, so a sentence that merely contains a keyword is not consent.
     *
     * @return string|null WhatsappConsentLog::ACTION_OPT_IN, WhatsappConsentLog::ACTION_OPT_OUT or null
     */
    public function matchKeyword(string $body): ?string {
        $normalisedBody = mb_strtoupper(trim($body));

        if (in_array($normalisedBody, self::OPT_IN_KEYWORDS, true)) {
            return WhatsappConsentLog::ACTION_OPT_IN;
        }
        if (in_array($normalisedBody, self::OPT_OUT_KEYWORDS, true)) {
            return WhatsappConsentLog::ACTION_OPT_OUT;
        }

        return null;
    }

    private function companyName(): string {
        return $this->mainSettings->newQuery()->value('company_name') ?? 'us';
    }
}
