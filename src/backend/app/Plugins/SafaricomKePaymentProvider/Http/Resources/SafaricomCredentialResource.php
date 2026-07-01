<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomKePaymentProvider\Http\Resources;

use App\Plugins\SafaricomKePaymentProvider\Models\SafaricomCredential;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SafaricomCredential
 *
 * consumer_key / consumer_secret / passkey are never returned to the client:
 * re-binding the stored ciphertext into the form and POSTing it back would
 * double-encrypt on the next save and silently corrupt the credential. The UI
 * shows a "configured" indicator instead and treats blank inputs as "no change".
 */
class SafaricomCredentialResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'id' => $this->resource->getAttribute('id'),
            'consumer_key_set' => !empty($this->resource->getAttribute('consumer_key')),
            'consumer_secret_set' => !empty($this->resource->getAttribute('consumer_secret')),
            'passkey_set' => !empty($this->resource->getAttribute('passkey')),
            'shortcode' => $this->resource->getAttribute('shortcode'),
            'environment' => $this->resource->getAttribute('environment'),
            'validation_url' => $this->resource->getAttribute('validation_url'),
            'confirmation_url' => $this->resource->getAttribute('confirmation_url'),
            'timeout_url' => $this->resource->getAttribute('timeout_url'),
            'result_url' => $this->resource->getAttribute('result_url'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
        ];
    }
}
