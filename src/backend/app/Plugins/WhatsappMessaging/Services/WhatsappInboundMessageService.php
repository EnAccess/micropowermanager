<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\MainSettings;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappMessageNotSentException;
use App\Plugins\WhatsappMessaging\Helpers\WhatsappPhoneNumber;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use Illuminate\Support\Carbon;

class WhatsappInboundMessageService {
    public const array OPT_IN_KEYWORDS = ['JOIN', 'START', 'YES'];
    public const array OPT_OUT_KEYWORDS = ['STOP', 'UNSUBSCRIBE'];

    private const string MESSAGE_TYPE_TEXT = 'text';

    public function __construct(
        private WhatsappContactService $contactService,
        private WhatsappCloudApiClient $cloudApiClient,
        private MainSettings $mainSettings,
    ) {}

    /**
     * @param array<string, mixed> $message
     *
     * @throws WhatsappMessageNotSentException when the confirmation reply fails; the consent change is already saved
     */
    public function handle(array $message): void {
        $contact = $this->contactService->findOrCreateByPhone(WhatsappPhoneNumber::fromWaId($message['from']));
        $this->contactService->recordInbound($contact, $message['from'], Carbon::createFromTimestamp((int) $message['timestamp']));

        if ($message['type'] !== self::MESSAGE_TYPE_TEXT) {
            return;
        }

        $body = $message['text']['body'] ?? '';

        $reply = match ($this->matchKeyword($body)) {
            WhatsappConsentLog::ACTION_OPT_IN => $this->contactService->optIn($contact, WhatsappConsentLog::SOURCE_KEYWORD, null, $body)
                ? sprintf('You are now subscribed to WhatsApp messages from %s. Reply STOP to unsubscribe.', $this->companyName())
                : null,
            WhatsappConsentLog::ACTION_OPT_OUT => $this->contactService->optOut($contact, WhatsappConsentLog::SOURCE_KEYWORD, null, $body)
                ? sprintf('You will no longer receive WhatsApp messages from %s. Reply JOIN to subscribe again.', $this->companyName())
                : null,
            default => null,
        };

        // Free text is allowed here: the customer has just written to us, so the customer service window is open.
        if ($reply !== null) {
            $this->cloudApiClient->sendMessage(new WhatsappOutboundMessage($contact->phone, $reply));
        }
    }

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
