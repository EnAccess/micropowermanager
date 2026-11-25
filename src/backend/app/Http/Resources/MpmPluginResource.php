<?php

namespace App\Http\Resources;

use App\Enums\MpmPlugin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property MpmPlugin $resource
 */
class MpmPluginResource extends JsonResource {
    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array {
        return [
            /* Unique identifier of the plugin. */
            'slug' => $this->resource->value,
            'display_name' => $this->resource->displayName(),
            'description' => $this->resource->description(),
            /* One of `mini-grid`, `shs`, `e-bike` or `general`. */
            'usage_type' => $this->resource->usageType(),
        ];
    }
}
