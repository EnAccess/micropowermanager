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
            'recipient_phone' => $this->recipient_phone,
            'notification_type' => $this->notification_type,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'sent_at' => $this->sent_at?->toISOString(),
            'provider_sent_at' => $this->provider_sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'provider_error_code' => $failed && is_string($code) && preg_match('/^[0-9]{1,20}$/D', $code) ? $code : null,
            // Older send-client errors can include arbitrary provider text or credentials.
            'error_message' => $failed ? 'Message delivery failed.' : null,
        ];
    }
}
