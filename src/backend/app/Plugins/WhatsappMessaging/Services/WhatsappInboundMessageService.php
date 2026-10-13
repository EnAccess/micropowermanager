<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\MainSettings;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappMessageNotSentException;
use App\Plugins\WhatsappMessaging\Helpers\WhatsappPhoneNumber;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class WhatsappInboundMessageService {
    public const array OPT_IN_KEYWORDS = ['JOIN', 'START', 'YES'];
    public const array OPT_OUT_KEYWORDS = ['STOP', 'UNSUBSCRIBE'];

    private const string MESSAGE_TYPE_TEXT = 'text';

    // Meta keeps retrying an unacknowledged webhook for up to 7 days, so a self-service reply is remembered that long.
    private const int SELF_SERVICE_DEDUPLICATION_DAYS = 7;

    public function __construct(
        private WhatsappContactService $contactService,
        private WhatsappCloudApiClient $cloudApiClient,
        private MainSettings $mainSettings,
        private WhatsappSelfServiceService $selfServiceService,
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
            default => $this->selfServiceReply($contact, $message['id'], $body),
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

    /**
     * Keyword replies are idempotent through the consent state, but self-service answers are not, so a resent webhook must not answer twice.
     */
    private function selfServiceReply(WhatsappContact $contact, string $messageId, string $body): ?string {
        if ($contact->opt_in_status !== WhatsappContact::OPT_IN_STATUS_OPTED_IN) {
            return null;
        }

        if (!Cache::add('whatsapp-inbound:'.$messageId, true, now()->addDays(self::SELF_SERVICE_DEDUPLICATION_DAYS))) {
            return null;
        }

        return $this->selfServiceService->reply($contact, $body);
    }

    private function companyName(): string {
        return $this->mainSettings->newQuery()->value('company_name') ?? 'us';
    }
}
