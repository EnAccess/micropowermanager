<?php

namespace App\Plugins\WhatsApp\Http\Resources;

use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WhatsAppMessage */
class WhatsAppMessageResource extends JsonResource {
    /** @return array<string, mixed> */
    public function toArray(Request $request): array {
        $failed = $this->status === 'failed';
        $code = $this->provider_error_code;

        return [
            'id' => $this->id,
            'message_id' => $this->message_id,
            'external_id' => $this->message_id,
            'recipient_phone' => $this->recipient_phone,
            'phone' => $this->recipient_phone,
            'customer' => $this->recipient_name ?: 'Customer',
            'notification_type' => $this->notification_type,
            'type' => WhatsAppMessage::TYPES[$this->notification_type] ?? ucfirst(str_replace('_', ' ', (string) $this->notification_type)),
            'status' => $this->status ? ucfirst($this->status) : 'Sent',
            'reference' => $this->reference,
            'created_at' => $this->created_at?->toISOString(),
            'date' => $this->created_at?->format('Y-m-d H:i:s'),
            'sent_at' => $this->sent_at?->toISOString(),
            'provider_sent_at' => $this->provider_sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'provider_error_code' => $code,
            'error_message' => $failed ? $this->humanizeError($this->error_message) : null,
        ];
    }

    private function humanizeError(?string $rawError): ?string {
        if (!$rawError) {
            return null;
        }

        if (str_contains($rawError, '131030') || str_contains($rawError, 'not in allowed list')) {
            return 'Recipient phone number is not registered in your Meta Developer Sandbox. Please add it to your allowed recipient list in Meta Developer Portal.';
        }

        if (str_contains($rawError, '131047') || str_contains($rawError, '24 hours') || str_contains($rawError, 'Re-engagement')) {
            return 'Customer service window closed. More than 24 hours have passed since the customer last replied.';
        }

        if (str_contains($rawError, '190') || str_contains($rawError, 'Session has expired') || str_contains($rawError, 'access token')) {
            return 'Meta API access token expired. Please generate a new token in Meta and save it in WhatsApp Settings.';
        }

        if (str_contains($rawError, 'Invalid recipient phone')) {
            return 'Invalid phone number. Please enter a valid international phone number with country code.';
        }

        return $rawError;
    }
}
