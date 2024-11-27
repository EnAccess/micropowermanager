<?php

namespace Database\Factories;

use App\Models\SubConnectionType;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubConnectionTypeFactory extends Factory {
    protected $model = SubConnectionType::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'name' => $this->faker->word,
            'connection_type_id' => $this->faker->numberBetween(1, 10),
            'tariff_id' => $this->faker->numberBetween(1, 10),
        ];
    }
}
