<?php

namespace App\Services;

use App\Models\GeographicalInformation;
use App\Models\Meter\Meter;

/**
 * @phpstan-type MeterLocationData array{
 *     id: int,
 *     lat: float|string|null,
 *     lng: float|string|null
 * }
 * @phpstan-type UpdateGeographicalResponse array{
 *     data: bool
 * }
 */
class MeterGeographicalInformationService {
    public function __construct(
        private Meter $meter,
    ) {}

    /**
     * @param array<int, MeterLocationData> $meters
     *
     * @return UpdateGeographicalResponse
     */
    public function updateGeographicalInformation(array $meters): array {
        collect($meters)->each(function ($meter) {
            if (!empty($meter['lat']) && !empty($meter['lng'])) {
                $latitude = (float) $meter['lat'];
                $longitude = (float) $meter['lng'];
                $meter = $this->meter->newQuery()->where('id', $meter['id'])
                    ->first();
                if ($meter) {
                    $geo = $meter->device->geo;
                    $geo->geo_json = GeographicalInformation::makePoint($latitude, $longitude);
                    $geo->save();
                }
            }
        });

        return ['data' => true];
    }
}
