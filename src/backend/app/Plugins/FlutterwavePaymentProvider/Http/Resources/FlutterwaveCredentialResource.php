<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Http\Resources;

use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveCredential;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Secrets are write-only: only whether a key is set is exposed, never its
 * stored value (encrypted or not) — matches the Pesapal plugin's pattern
 * rather than Paystack's, which currently echoes the raw stored attribute.
 *
 * @mixin FlutterwaveCredential
 */
class FlutterwaveCredentialResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'id' => $this->resource->getAttribute('id'),
            'public_key_set' => filled($this->resource->getAttribute('public_key')),
            'secret_key_set' => filled($this->resource->getAttribute('secret_key')),
            'encryption_key_set' => filled($this->resource->getAttribute('encryption_key')),
            'webhook_secret_hash_set' => filled($this->resource->getAttribute('webhook_secret_hash')),
            'callback_url' => $this->resource->getAttribute('callback_url'),
            'merchant_name' => $this->resource->getAttribute('merchant_name'),
            'merchant_email' => $this->resource->getAttribute('merchant_email'),
            'environment' => $this->resource->getAttribute('environment'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
        ];
    }
}
