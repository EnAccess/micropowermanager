<?php

namespace App\Plugins\Whatsapp\Http\Resources;

use App\Plugins\Whatsapp\Models\WhatsappCredential;
use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappCredentialResource extends JsonResource {
    /**
     * Secrets are never sent back to the browser.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        /** @var WhatsappCredential $credential */
        $credential = $this->resource;

        return [
            'id' => $credential->id,
            'phone_number_id' => $credential->phone_number_id,
            'waba_id' => $credential->waba_id,
            'graph_version' => $credential->graph_version,
            'template_language' => $credential->template_language,
            'receipt_template' => $credential->receipt_template,
            'reminder_template' => $credential->reminder_template,
            'fallback_to_sms' => (bool) $credential->fallback_to_sms,
            'webhook_verify_token' => $credential->webhook_verify_token,
            'has_token' => !empty($credential->access_token),
            'has_app_secret' => !empty($credential->app_secret),
        ];
    }
}
