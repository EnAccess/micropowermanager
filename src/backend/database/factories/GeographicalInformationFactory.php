<?php

namespace Database\Factories;

use App\Models\GeographicalInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

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
     *
     * @return Factory
     */
    public function randomizePointsInVillage() {
        return $this->state(function (array $attributes) {
            return [
                'points' => $this->add_random_offset_to_coordinates($attributes['points'], 1000),
            ];
        });
    }

    /**
     * Randomize the location points by approx 10m.
     *
     * @return Factory
     */
    public function randomizePointsInHousehold() {
        return $this->state(function (array $attributes) {
            return [
                'points' => $this->add_random_offset_to_coordinates($attributes['points'], 10),
            ];
        });
    }

    public function definition() {
        return [
            'points' => '0.000000,0.000000',
        ];
    }
}
