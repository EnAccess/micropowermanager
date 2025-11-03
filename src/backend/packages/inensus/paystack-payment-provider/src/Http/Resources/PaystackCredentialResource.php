<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Inensus\PaystackPaymentProvider\Models\PaystackCredential;

/**
 * @mixin PaystackCredential
 */
class PaystackCredentialResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'id' => $this->resource->getAttribute('id'),
            'secret_key' => $this->resource->getAttribute('secret_key'),
            'public_key' => $this->resource->getAttribute('public_key'),
            'callback_url' => $this->resource->getAttribute('callback_url'),
            'merchant_name' => $this->resource->getAttribute('merchant_name'),
            'environment' => $this->resource->getAttribute('environment'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
        ];
    }
}
