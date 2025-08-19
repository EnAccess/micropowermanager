<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaystackCredentialResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'secret_key' => $this->secret_key,
            'public_key' => $this->public_key,
            'webhook_secret' => $this->webhook_secret,
            'callback_url' => $this->callback_url,
            'merchant_name' => $this->merchant_name,
            'environment' => $this->environment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
