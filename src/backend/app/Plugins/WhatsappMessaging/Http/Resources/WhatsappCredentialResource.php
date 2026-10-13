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
            'sms_fallback_enabled' => $this->sms_fallback_enabled,
            'sms_unit_cost' => $this->sms_unit_cost,
            'whatsapp_utility_cost' => $this->whatsapp_utility_cost,
            'cost_currency' => $this->cost_currency,
            'webhook_url' => config('app.url').'/api/whatsapp-messaging/webhook/'.$request->attributes->get('companyId'),
            'webhook_verify_token' => $this->webhook_verify_token,
            'opt_in_link' => $this->optInLink(),
            'is_access_token_set' => !empty($this->access_token),
            'access_token_last_four' => $this->lastFour($this->access_token),
            'is_app_secret_set' => !empty($this->app_secret),
            'app_secret_last_four' => $this->lastFour($this->app_secret),
        ];
    }

    /**
     * A click-to-chat link that opens WhatsApp with the JOIN keyword typed, for SMS footers and printed QR codes.
     * It is null until a successful connection test has stored the display number.
     */
    private function optInLink(): ?string {
        if (in_array($this->display_phone_number, [null, ''], true)) {
            return null;
        }

        // wa.me expects the number as digits only, without "+", spaces or dashes.
        return 'https://wa.me/'.preg_replace('/\D/', '', $this->display_phone_number).'?text=JOIN';
    }

    private function lastFour(?string $secret): ?string {
        return in_array($secret, [null, '', '0'], true) ? null : substr($secret, -4);
    }
}
