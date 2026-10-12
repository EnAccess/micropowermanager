<?php

namespace App\Plugins\WhatsappMessaging\Services;

use Illuminate\Support\Facades\Log;

class WhatsappWebhookService {
    public function __construct(
        private WhatsappInboundMessageService $inboundMessageService,
    ) {}

    /**
     * Meta batches several entries/changes into one delivery and resends the whole
     * batch on any non-200, so a failing item is logged and skipped instead of
     * aborting the rest.
     *
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload, string $phoneNumberId): void {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];
                if (($value['metadata']['phone_number_id'] ?? null) !== $phoneNumberId) {
                    continue;
                }

                $this->handleStatuses($value['statuses'] ?? []);
                $this->handleMessages($value['messages'] ?? []);
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $statuses
     */
    private function handleStatuses(array $statuses): void {
        foreach ($statuses as $status) {
            $this->safely('status', function () use ($status): void {
                // Handed to the delivery status service once WA-09 lands.
                Log::info('WhatsApp status received', ['status' => $status]);
            });
        }
    }

    /**
     * Deliberately not SmsStoredEvent: SmsTransactionParser would turn a
     * customer-typed "payment" text into a real payment.
     *
     * @param array<int, array<string, mixed>> $messages
     */
    private function handleMessages(array $messages): void {
        foreach ($messages as $message) {
            $this->safely('message', fn () => $this->inboundMessageService->handle($message));
        }
    }

    private function safely(string $itemType, callable $handler): void {
        try {
            $handler();
        } catch (\Throwable $exception) {
            Log::error('Failed to process WhatsApp webhook '.$itemType, ['error' => $exception->getMessage()]);
        }
    }
}
