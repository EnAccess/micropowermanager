<?php

namespace App\Plugins\StsMeterVend\Http\Resources;

use App\Models\Meter\Meter;
use App\Plugins\StsMeterVend\Models\StsMeter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Meter
 */
class StsManagedMeterResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $person = $this->device?->person;
        $stsMeter = $this->resource->getRelation('stsMeter');

        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'owner' => $person === null ? null : trim("{$person->name} {$person->surname}"),
            'tariff' => $this->tariff?->name,
            'key_present' => $stsMeter instanceof StsMeter,
            'base_date' => $stsMeter?->base_date->value,
            'encryption_algorithm' => $stsMeter?->encryption_algorithm->value,
            'last_issued_at' => $stsMeter?->last_issued_at?->toDateTimeString(),
        ];
    }
}
