<?php

namespace Database\Factories;

use App\Models\SubTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubTargetFactory extends Factory {
    protected $model = SubTarget::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'target_id' => $this->faker->randomNumber(1, 10),
            'connection_id' => $this->faker->randomNumber(1, 10),
            'revenue' => $this->faker->randomNumber(1, 10),
            'new_connections' => $this->faker->randomNumber(1, 10),
            'connected_power' => $this->faker->randomNumber(1, 10),
            'energy_per_month' => $this->faker->randomNumber(1, 10),
            'average_revenue_per_month' => $this->faker->randomNumber(1, 10),
        ];
    }
}
