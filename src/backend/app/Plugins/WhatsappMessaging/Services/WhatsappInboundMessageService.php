<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\MainSettings;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappMessageNotSentException;
use App\Plugins\WhatsappMessaging\Helpers\WhatsappPhoneNumber;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use Illuminate\Support\Carbon;

class WhatsappInboundMessageService {
    public const array OPT_IN_KEYWORDS = ['JOIN', 'START', 'YES'];
    public const array OPT_OUT_KEYWORDS = ['STOP', 'UNSUBSCRIBE'];

    public const string OPT_IN_BUTTON_ID = 'mpm_opt_in';
    public const string OPT_OUT_BUTTON_ID = 'mpm_opt_out';

    private const string MESSAGE_TYPE_TEXT = 'text';
    private const string MESSAGE_TYPE_INTERACTIVE = 'interactive';

    private const string OPT_IN_PROMPT_TEXT = 'Get your payment receipts and reminders here on WhatsApp?';

    // Meta rejects reply button titles longer than 20 characters.
    private const array OPT_IN_PROMPT_BUTTONS = [
        self::OPT_IN_BUTTON_ID => 'Yes, subscribe',
        self::OPT_OUT_BUTTON_ID => 'No, thanks',
    ];

    private const int OPT_IN_PROMPT_INTERVAL_DAYS = 30;

    public function __construct(
        private WhatsappContactService $contactService,
        private WhatsappCloudApiClient $cloudApiClient,
        private MainSettings $mainSettings,
    ) {}

    /**
     * @param array<string, mixed> $message
     *
     * @throws WhatsappMessageNotSentException when the confirmation reply or the opt-in prompt fails; a consent change is already saved
     */
    public function handle(array $message): void {
        $contact = $this->contactService->findOrCreateByPhone(WhatsappPhoneNumber::fromWaId($message['from']));
        $this->contactService->recordInbound($contact, $message['from'], Carbon::createFromTimestamp((int) $message['timestamp']));

        match ($message['type']) {
            self::MESSAGE_TYPE_TEXT => $this->handleText($contact, $message['text']['body'] ?? ''),
            self::MESSAGE_TYPE_INTERACTIVE => $this->handleButtonReply($contact, $message['interactive']['button_reply'] ?? []),
            default => null,
        };
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

    private function handleText(WhatsappContact $contact, string $body): void {
        $action = $this->matchKeyword($body);

        if ($action === null) {
            $this->promptOptInIfDue($contact);

            return;
        }

        $this->applyConsent($contact, $action, $body);
    }

    /**
     * @param array<string, mixed> $buttonReply
     */
    private function handleButtonReply(WhatsappContact $contact, array $buttonReply): void {
        $action = match ($buttonReply['id'] ?? null) {
            self::OPT_IN_BUTTON_ID => WhatsappConsentLog::ACTION_OPT_IN,
            self::OPT_OUT_BUTTON_ID => WhatsappConsentLog::ACTION_OPT_OUT,
            default => null,
        };

        if ($action === null) {
            return;
        }

        // A tap is consent given by the customer just like a typed keyword; the title records what they tapped.
        $this->applyConsent($contact, $action, $buttonReply['title'] ?? $buttonReply['id']);
    }

    private function applyConsent(WhatsappContact $contact, string $action, string $rawMessage): void {
        $reply = match ($action) {
            WhatsappConsentLog::ACTION_OPT_IN => $this->contactService->optIn($contact, WhatsappConsentLog::SOURCE_KEYWORD, null, $rawMessage)
                ? sprintf('You are now subscribed to WhatsApp messages from %s. Reply STOP to unsubscribe.', $this->companyName())
                : null,
            WhatsappConsentLog::ACTION_OPT_OUT => $this->contactService->optOut($contact, WhatsappConsentLog::SOURCE_KEYWORD, null, $rawMessage)
                ? sprintf('You will no longer receive WhatsApp messages from %s. Reply JOIN to subscribe again.', $this->companyName())
                : null,
        };

        // Free text is allowed here: the customer has just written to us, so the customer service window is open.
        if ($reply !== null) {
            $this->cloudApiClient->sendMessage(new WhatsappOutboundMessage($contact->phone, $reply));
        }
    }

    /**
     * The prompt is only ever a reply to a message the customer just sent, never a template to someone who did not write: that would be unsolicited messaging.
     */
    private function promptOptInIfDue(WhatsappContact $contact): void {
        if ($contact->opt_in_status === WhatsappContact::OPT_IN_STATUS_OPTED_IN) {
            return;
        }

        if ($contact->opt_in_prompted_at?->isAfter(Carbon::now()->subDays(self::OPT_IN_PROMPT_INTERVAL_DAYS)) ?? false) {
            return;
        }

        $this->cloudApiClient->sendInteractiveButtons($contact->phone, self::OPT_IN_PROMPT_TEXT, self::OPT_IN_PROMPT_BUTTONS);

        // Recorded only after Meta accepted the prompt, so a failed send is retried on the customer's next message.
        $this->contactService->recordOptInPrompt($contact);
    }

    private function companyName(): string {
        return $this->mainSettings->newQuery()->value('company_name') ?? 'us';
    }
}
