<?php

namespace App\Http\Resources;

use App\Services\ImportServices\ImportResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ImportResult $resource
 */
class ImportResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return $this->resource->toArray();
    }
}
