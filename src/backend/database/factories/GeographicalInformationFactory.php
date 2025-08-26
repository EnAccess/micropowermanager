<?php

namespace Database\Factories;

use App\Models\GeographicalInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GeographicalInformation> */
class GeographicalInformationFactory extends Factory {
    protected $model = GeographicalInformation::class;

    private function add_random_offset_to_coordinates(string $coord_string, int $distance): string {
        list($lat, $lng) = explode(',', $coord_string);

        $lat = (float) $lat;
        $lng = (float) $lng;

        // 0.01 is approx 1km when close to equator
        $new_lat = $lat + (mt_rand(-100, 100) / 10000) * ($distance / 1000);
        $new_lng = $lng + (mt_rand(-100, 100) / 10000) * ($distance / 1000);

        return $new_lat.','.$new_lng;
    }

    /**
     * Randomize the location points by approx 1km.
     */
    public function randomizePointsInVillage(): static {
        return $this->state(function (array $attributes) {
            return [
                'points' => $this->add_random_offset_to_coordinates($attributes['points'], 1000),
            ];
        });
    }

    /**
     * Randomize the location points by approx 10m.
     */
    public function randomizePointsInHousehold(): static {
        return $this->state(function (array $attributes) {
            return [
                'points' => $this->add_random_offset_to_coordinates($attributes['points'], 10),
            ];
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'points' => '0.000000,0.000000',
        ];
    }
}
