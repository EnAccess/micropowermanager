<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PesapalResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'redirect_url' => $this['redirect_url'] ?? null,
            'order_tracking_id' => $this['order_tracking_id'] ?? null,
            'merchant_reference' => $this['merchant_reference'] ?? null,
            'error' => $this['error'] ?? null,
        ];
    }
}
