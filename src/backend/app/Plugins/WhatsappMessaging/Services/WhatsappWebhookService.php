<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookService {
    public function __construct(private WhatsappContactService $contactService, private WhatsappMessageService $messageService, private WhatsappSelfServiceService $selfService, private WhatsappApiClient $apiClient) {}

    /** @param array<string, mixed> $payload Meta resends the whole batch on any non-200, so a failing item is logged and skipped */
    public function handle(array $payload, WhatsappCredential $credential): void {
        foreach (array_merge(...array_column($payload['entry'] ?? [], 'changes')) as $change) {
            if (($change['field'] ?? null) !== 'messages' || ($change['value']['metadata']['phone_number_id'] ?? null) !== $credential->phone_number_id) {
                continue;
            }
            array_map(fn (array $status) => $this->safely(fn () => $this->messageService->applyStatus($status)), $change['value']['statuses'] ?? []);
            array_map(fn (array $message) => $this->safely(fn () => $this->handleMessage($message, $credential)), $change['value']['messages'] ?? []);
        }
    }

    /** @param array<string, mixed> $message never an SmsStoredEvent, which would turn a customer-typed "payment" text into a real payment */
    private function handleMessage(array $message, WhatsappCredential $credential): void {
        $contact = $this->contactService->receiveFrom($message['from'], (int) $message['timestamp']);
        $button = $message['interactive']['button_reply'] ?? null;
        $text = $button['title'] ?? $message['text']['body'] ?? '';
        // Only a whole message is a keyword, so "I don't want to STOP" is not an opt-out.
        $action = WhatsappConsentLog::KEYWORD_ACTIONS[mb_strtoupper(trim($button['id'] ?? $text))] ?? null;

        if ($action !== null) {
            if ($this->contactService->changeConsent($contact, $action, WhatsappConsentLog::SOURCE_KEYWORD, null, $text)) {
                $this->apiClient->sendMessage($credential, $contact->phone, ['type' => 'text', 'text' => ['body' => WhatsappConsentLog::CONFIRMATIONS[$action]]]);
            }
        } elseif ($message['type'] === 'text' && $contact->opt_in_status === WhatsappContact::OPT_IN_STATUS_OPTED_IN) {
            // Meta retries a webhook for up to 7 days; consent is idempotent by itself, but a bot answer must not be sent twice.
            if (Cache::add('whatsapp-inbound:'.$message['id'], true, now()->addDays(7))) {
                $this->apiClient->sendMessage($credential, $contact->phone, ['type' => 'text', 'text' => ['body' => $this->selfService->reply($contact, $text)]]);
            }
        } elseif ($message['type'] === 'text' && $contact->isOptInPromptDue()) {
            $this->apiClient->sendMessage($credential, $contact->phone, WhatsappConsentLog::OPT_IN_PROMPT);
            // Recorded only after Meta accepted the prompt, so a failed send is retried on the customer's next message.
            $contact->update(['opt_in_prompted_at' => now()]);
        }
    }

    private function safely(callable $handler): void {
        try {
            $handler();
        } catch (\Throwable $exception) {
            Log::error('Failed to process a WhatsApp webhook item', ['error' => $exception->getMessage()]);
        }
    }
}
