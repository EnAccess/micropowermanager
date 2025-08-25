<?php

namespace Database\Factories;

use App\Models\SubTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SubTarget> */
class SubTargetFactory extends Factory {
    protected $model = SubTarget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'target_id' => $this->faker->randomNumber(2),
            'connection_id' => $this->faker->randomNumber(2),
            'revenue' => $this->faker->randomNumber(2),
            'new_connections' => $this->faker->randomNumber(2),
            'connected_power' => $this->faker->randomNumber(2),
            'energy_per_month' => $this->faker->randomNumber(2),
            'average_revenue_per_month' => $this->faker->randomNumber(2),
        ];
    }
}
