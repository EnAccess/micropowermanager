<?php

namespace App\Http\Resources;

use App\Enums\PaymentInitiationProvider;
use App\Services\MpmPluginCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PaymentInitiationProvider $resource
 */
class PaymentProviderResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            /*
             * Legacy integer id of the payment provider.
             *
             * @deprecated use `slug`
             */
            'id' => $this->resource->legacyId(),
            /** @var PaymentInitiationProvider Identifier of the payment provider, to send as `payment_provider`. */
            'slug' => $this->resource->value,
            /** @var string Human-readable name of the payment provider. */
            'name' => resolve(MpmPluginCatalog::class)->displayName($this->resource->value),
        ];
    }
}
