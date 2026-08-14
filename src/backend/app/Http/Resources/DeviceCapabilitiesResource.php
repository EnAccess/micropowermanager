<?php

namespace App\Http\Resources;

use App\Lib\DeviceCapabilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeviceCapabilities
 */
class DeviceCapabilitiesResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            /* Whether a token can be generated for this device without a customer payment. */
            'token_generation' => $this->tokenGeneration,
            /* The unit this device's tokens carry credit in, next to a plain currency amount. */
            'credit_unit' => $this->creditUnit?->value,
            /* Why this device cannot be issued a token, e.g. it has no customer or no price to convert money into credit. Null when it can. */
            'token_generation_blocked_reason' => $this->tokenGenerationBlockedReason,
        ];
    }
}
