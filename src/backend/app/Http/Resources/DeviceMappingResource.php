<?php

namespace App\Http\Resources;

use App\Lib\DeviceMappingResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeviceMappingResult
 */
class DeviceMappingResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            /* Whether the device's manufacturer exposes a device management API. */
            'supported' => $this->supported,
            /* Whether the device is still mapped to this company on the manufacturer side. Omitted when unsupported. */
            'mapped' => $this->when($this->supported, $this->mapped),
            /*
             * Manufacturer-specific device details, when the manufacturer returns them. Omitted when unsupported.
             *
             * @var array<string, mixed>|null
             */
            'device' => $this->when($this->supported, $this->device),
        ];
    }
}
