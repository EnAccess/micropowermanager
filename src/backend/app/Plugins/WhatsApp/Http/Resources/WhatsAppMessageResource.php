<?php

namespace App\Plugins\WhatsApp\Http\Resources;

use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WhatsAppMessage
 */
class WhatsAppMessageResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'date' => $this->created_at?->format('Y-m-d H:i'),
            'customer' => $this->recipient_name,
            'phone' => $this->recipient_phone,
            'type' => $this->typeLabel(),
            'status' => $this->statusLabel(),
            'external_id' => $this->message_id,
            'reference' => $this->reference,
            'notification_type' => $this->notification_type,
            'error_message' => $this->error_message,
        ];
    }
}
