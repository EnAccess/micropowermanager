<?php

namespace App\Http\Resources;

use App\Enums\PaymentInitiationProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentProviderResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            /** @var PaymentInitiationProvider The MpmPlugin ID of the payment provider. */
            'id' => $this->resource['id'],
            /** @var string Human-readable name of the payment provider. */
            'name' => $this->resource['name'],
        ];
    }
}
