<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookService {
    private const array TEMPLATE_FIELDS = ['message_template_status_update', 'template_category_update'];

    public function __construct(private WhatsappContactService $contactService, private WhatsappMessageService $messageService, private WhatsappSelfServiceService $selfService, private WhatsappTemplateService $templateService, private WhatsappApiClient $apiClient) {}

    /**
     * Message events name the phone number they belong to; template events only name the business account, which is the entry id.
     *
     * @param array<string, mixed> $payload Meta resends the whole batch on any non-200, so a failing item is logged and skipped
     */
    public function handle(array $payload, WhatsappCredential $credential): void {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $field = $change['field'] ?? null;

                if (in_array($field, self::TEMPLATE_FIELDS, true) && (string) ($entry['id'] ?? '') === $credential->business_account_id) {
                    $this->safely(fn () => $this->templateService->applyWebhook($credential, $change['value'] ?? []));
                } elseif ($field === 'messages' && ($change['value']['metadata']['phone_number_id'] ?? null) === $credential->phone_number_id) {
                    array_map(fn (array $status) => $this->safely(fn () => $this->messageService->applyStatus($status)), $change['value']['statuses'] ?? []);
                    array_map(fn (array $message) => $this->safely(fn () => $this->handleMessage($message, $credential)), $change['value']['messages'] ?? []);
                }
            }
        }
    }

    /** @param array<string, mixed> $message never an SmsStoredEvent, which would turn a customer-typed "payment" text into a real payment */
    private function handleMessage(array $message, WhatsappCredential $credential): void {
        $contact = $this->contactService->receiveFrom($message['from'], (int) $message['timestamp']);
        $text = $message['text']['body'] ?? '';
        $action = WhatsappConsentLog::KEYWORD_ACTIONS[mb_strtoupper(trim($text))] ?? null;

        if ($action !== null) {
            if ($this->contactService->changeConsent($contact, $action, WhatsappConsentLog::SOURCE_KEYWORD, null, $text)) {
                $this->apiClient->sendMessage($credential, $contact->phone, ['type' => 'text', 'text' => ['body' => WhatsappConsentLog::CONFIRMATIONS[$action]]]);
            }
        } elseif ($message['type'] === 'text' && $contact->opt_in_status === WhatsappContact::OPT_IN_STATUS_OPTED_IN) {
            if (Cache::add('whatsapp-inbound:'.$message['id'], true, now()->addDays(7))) {
                $this->apiClient->sendMessage($credential, $contact->phone, ['type' => 'text', 'text' => ['body' => $this->selfService->reply($contact, $text)]]);
            }
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
