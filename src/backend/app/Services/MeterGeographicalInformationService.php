<?php

namespace App\Services;

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
            $points = [
                $meter['lat'],
                $meter['lng'],
            ];
            if (!empty($meter['lat']) && !empty($meter['lng'])) {
                $meter = $this->meter->newQuery()->where('id', $meter['id'])
                    ->first();
                if ($meter) {
                    $geo = $meter->device->person->addresses()->first()->geo;
                    $geo->points = $points[0].','.$points[1];
                    $geo->save();
                }
            }
        });

        return ['data' => true];
    }
}
