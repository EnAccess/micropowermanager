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
        $validCode = $failed && is_string($code) && preg_match('/^[0-9]{1,20}$/D', $code) ? $code : null;

        return [
            'id' => $this->id,
            'message_id' => $this->message_id,
            'recipient_phone' => $this->recipient_phone,
            'notification_type' => $this->notification_type,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'sent_at' => $this->sent_at?->toISOString(),
            'provider_sent_at' => $this->provider_sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'provider_error_code' => $validCode,
            'error_message' => $failed ? $this->humanizeErrorCode($validCode) : null,
        ];
    }

    private function humanizeErrorCode(?string $code): string {
        return match ($code) {
            '131030' => 'Recipient phone number is not registered in your Meta Developer Sandbox. Please add it to your allowed recipient list in Meta Developer Portal.',
            '131031' => 'WhatsApp Business account is temporarily locked or restricted by Meta. System will automatically route to SMS fallback.',
            '131047' => 'Customer service window closed. More than 24 hours have passed since the customer last replied.',
            '190' => 'Meta API access token expired. Please generate a new token in Meta and save it in WhatsApp Settings.',
            default => 'Message delivery failed.',
        };
    }
}
