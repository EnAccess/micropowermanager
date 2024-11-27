<?php

namespace Database\Factories;

use App\Models\City;
use Faker\Provider\en_NG\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory {
    protected $model = City::class;

    public function __construct(
    ) {
        parent::__construct(...func_get_args());
        $this->faker->addProvider(new Address($this->faker));
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'name' => $this->faker->city,
            'country_id' => 1,
        ];
    }
}
