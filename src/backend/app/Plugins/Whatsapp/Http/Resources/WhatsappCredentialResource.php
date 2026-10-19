<?php

namespace App\Plugins\Whatsapp\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappCredentialResource extends JsonResource {
    /**
     * Secrets are never sent back to the browser.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'phone_number_id' => $this->phone_number_id,
            'waba_id' => $this->waba_id,
            'graph_version' => $this->graph_version,
            'template_language' => $this->template_language,
            'receipt_template' => $this->receipt_template,
            'reminder_template' => $this->reminder_template,
            'fallback_to_sms' => (bool) $this->fallback_to_sms,
            'webhook_verify_token' => $this->webhook_verify_token,
            'has_token' => !empty($this->access_token),
            'has_app_secret' => !empty($this->app_secret),
        ];
    }
}
