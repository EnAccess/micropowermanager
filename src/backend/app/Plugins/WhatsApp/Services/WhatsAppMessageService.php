<?php

namespace App\Plugins\WhatsApp\Services;

use App\Plugins\WhatsApp\Models\WhatsAppMessage;

class WhatsAppMessageService {
    public function __construct(
        private WhatsAppMessage $messageModel,
        private WhatsAppApiClient $apiClient,
    ) {}

    /**
     * @param array<string, mixed> $attributes
     */
    public function queue(array $attributes): WhatsAppMessage {
        $attributes['status'] = WhatsAppMessage::STATUS_QUEUED;

        return $this->messageModel->newQuery()->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function recordFailure(array $attributes, string $error): WhatsAppMessage {
        $attributes['status'] = WhatsAppMessage::STATUS_FAILED;
        $attributes['error_message'] = $error;

        return $this->messageModel->newQuery()->create($attributes);
    }

    public function find(int $id): ?WhatsAppMessage {
        return $this->messageModel->newQuery()->find($id);
    }

    /**
     * A message with the same type and source is still in flight or already
     * delivered; queueing another would duplicate the customer notification.
     */
    public function hasActiveMessage(string $notificationType, string $reference): bool {
        return $this->messageModel->newQuery()
            ->where('notification_type', $notificationType)
            ->where('reference', $reference)
            ->whereIn('status', [
                WhatsAppMessage::STATUS_QUEUED,
                WhatsAppMessage::STATUS_SENDING,
                WhatsAppMessage::STATUS_SENT,
                WhatsAppMessage::STATUS_DELIVERED,
                WhatsAppMessage::STATUS_READ,
            ])
            ->exists();
    }

    /**
     * @throws \Throwable
     */
    public function deliver(WhatsAppMessage $message): WhatsAppMessage {
        // The status guard makes redelivery idempotent: a retried job for a
        // message that already left "queued" must not hit the API again.
        if ($message->status !== WhatsAppMessage::STATUS_QUEUED) {
            return $message;
        }

        $this->applyStatus($message, WhatsAppMessage::STATUS_SENDING);

        try {
            $externalMessageId = $this->apiClient->sendTextMessage($message->recipient_phone, (string) $message->body);

            $this->applyStatus($message, WhatsAppMessage::STATUS_SENT, [
                'message_id' => $externalMessageId,
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $exception) {
            $this->applyStatus($message, WhatsAppMessage::STATUS_FAILED, [
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $message;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function applyStatus(WhatsAppMessage $message, string $status, array $attributes = []): void {
        $message->update(['status' => $status, ...$attributes]);
        $message->refresh();
    }
}
