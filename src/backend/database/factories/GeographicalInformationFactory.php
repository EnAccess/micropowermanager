<?php

namespace Database\Factories;

use App\Models\GeographicalInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GeographicalInformation> */
class GeographicalInformationFactory extends Factory {
    protected $model = GeographicalInformation::class;

    /**
     * Randomize the location by approx 1km.
     */
    public function randomizePointsInVillage(): static {
        return $this->offsetCoordinates(1000);
    }

    /**
     * Randomize the location by approx 10m.
     */
    public function randomizePointsInHousehold(): static {
        return $this->offsetCoordinates(10);
    }

    private function offsetCoordinates(int $distanceInMeters): static {
        return $this->state(function (array $attributes) use ($distanceInMeters): array {
            [$latitude, $longitude] = $this->coordinatesFromGeoJson($attributes['geo_json']);

            // 0.01 is approx 1km when close to equator
            $newLatitude = $latitude + (mt_rand(-100, 100) / 10000) * ($distanceInMeters / 1000);
            $newLongitude = $longitude + (mt_rand(-100, 100) / 10000) * ($distanceInMeters / 1000);

            return ['geo_json' => GeographicalInformation::makePoint($newLatitude, $newLongitude)];
        });
    }

    /**
     * @param array<string, mixed>|object $geoJson
     *
     * @return array{0: float, 1: float}
     */
    private function coordinatesFromGeoJson(array|object $geoJson): array {
        $geoJson = json_decode(json_encode($geoJson), true);
        $coordinates = $geoJson['geometry']['coordinates'] ?? [0, 0];

        return [(float) ($coordinates[1] ?? 0), (float) ($coordinates[0] ?? 0)];
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'geo_json' => GeographicalInformation::makePoint(0.0, 0.0),
        ];
    }
}
