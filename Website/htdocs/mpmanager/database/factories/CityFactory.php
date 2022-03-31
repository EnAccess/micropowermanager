<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{

    protected $model = City::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->city,
            'country_id' => 1,
            'cluster_id' => 1,
            'mini_grid_id' => 1,
        ];
    }
}
