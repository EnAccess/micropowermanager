<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Http\Resources;

use App\Plugins\PesapalPaymentProvider\Models\PesapalCredential;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PesapalCredential
 *
 * Consumer key/secret are never returned to the client: re-binding the
 * stored ciphertext into the form and POSTing it back would double-encrypt
 * on the next save and silently corrupt the credential. The UI shows a
 * "configured" indicator instead and treats blank inputs as "no change".
 */
class PesapalCredentialResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'id' => $this->resource->getAttribute('id'),
            'consumer_key_set' => !empty($this->resource->getAttribute('consumer_key')),
            'consumer_secret_set' => !empty($this->resource->getAttribute('consumer_secret')),
            'callback_url' => $this->resource->getAttribute('callback_url'),
            'merchant_name' => $this->resource->getAttribute('merchant_name'),
            'merchant_email' => $this->resource->getAttribute('merchant_email'),
            'environment' => $this->resource->getAttribute('environment'),
            'currency' => $this->resource->getAttribute('currency'),
            'ipn_id' => $this->resource->getAttribute('ipn_id'),
            'ipn_registered_at' => $this->resource->getAttribute('ipn_registered_at'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
        ];
    }
}
