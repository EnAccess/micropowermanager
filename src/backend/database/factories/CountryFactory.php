<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Country> */
class CountryFactory extends Factory {
    protected $model = Country::class;

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
