<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'country_code' => $this->faker->countryCode(),
            'country_name' => $this->faker->country(),
        ];
    }
}
