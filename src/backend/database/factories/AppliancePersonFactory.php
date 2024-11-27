<?php

namespace Database\Factories;

use App\Models\AssetPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppliancePersonFactory extends Factory {
    protected $model = AssetPerson::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'person_id' => $this->faker->numberBetween(1, 10),
            'first_payment_date' => $this->faker->date(),
            'down_payment' => $this->faker->randomFloat(2, 1, 100),
            'rate_count' => $this->faker->numberBetween(1, 10),
            'total_cost' => $this->faker->randomFloat(2, 1, 100),
            'creator_type' => $this->faker->randomElement(['user', 'agent']),
            'creator_id' => $this->faker->numberBetween(1, 10),
        ];
    }
}
