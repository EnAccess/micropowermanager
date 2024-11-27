<?php

namespace Database\Factories;

use App\Models\AgentCharge;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentChargeFactory extends Factory {
    protected $model = AgentCharge::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'user_id' => 1,
            'amount' => $this->faker->randomDigitNotNull() * 100000,
        ];
    }
}
