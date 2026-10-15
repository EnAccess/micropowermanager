<?php

namespace App\Plugins\WhatsApp\Http\Resources;

use App\Plugins\WhatsApp\Models\WhatsAppCredential;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WhatsAppCredential
 */
class WhatsAppResource extends JsonResource {
    /**
     * Transform the resource into an array for the API response.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'phone_number_id' => $this->phone_number_id,
            'whatsapp_business_account_id' => $this->whatsapp_business_account_id,
            'sender_phone_number' => $this->sender_phone_number,
            'webhook_verify_token' => $this->webhook_verify_token ? '••••••••' : null,
            'api_access_token' => $this->api_access_token ? '••••••••' : null,
            'has_api_access_token' => !empty($this->api_access_token),
            'has_webhook_verify_token' => !empty($this->webhook_verify_token),
            'api_version' => $this->api_version,
            'is_enabled' => (bool) $this->is_enabled,
            'notifications' => [
                'payment_receipts' => (bool) ($this->notification_settings['payment_receipts'] ?? true),
                'low_balance_warnings' => (bool) ($this->notification_settings['low_balance_warnings'] ?? true),
                'token_deliveries' => (bool) ($this->notification_settings['token_deliveries'] ?? true),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
