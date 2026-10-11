<?php

namespace App\Plugins\WhatsappMessaging\Http\Resources;

use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WhatsappCredential
 */
class WhatsappCredentialResource extends JsonResource {
    /**
     * Secrets are only ever exposed as "is set" plus their last four characters:
     * a leaked access token lets anyone message every customer as the operator.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'phone_number_id' => $this->phone_number_id,
            'business_account_id' => $this->business_account_id,
            'graph_api_version' => $this->graph_api_version,
            'sms_fallback_enabled' => (bool) $this->sms_fallback_enabled,
            'webhook_url' => config('app.url').'/api/whatsapp-messaging/webhook/'.$request->attributes->get('companyId'),
            'webhook_verify_token' => $this->webhook_verify_token,
            'is_access_token_set' => !empty($this->access_token),
            'access_token_last_four' => $this->lastFour($this->access_token),
            'is_app_secret_set' => !empty($this->app_secret),
            'app_secret_last_four' => $this->lastFour($this->app_secret),
        ];
    }

    private function lastFour(?string $secret): ?string {
        return empty($secret) ? null : substr($secret, -4);
    }
}
