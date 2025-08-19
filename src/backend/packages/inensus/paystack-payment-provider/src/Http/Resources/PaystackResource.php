<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaystackResource extends JsonResource {
    public function toArray($request): array {
        return [
            'redirectionUrl' => $this['redirectionUrl'],
            'reference' => $this['reference'],
            'error' => $this['error'],
        ];
    }
}
