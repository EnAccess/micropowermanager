<?php

namespace Database\Factories;

use App\Models\AgentReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentReceiptFactory extends Factory {
    protected $model = AgentReceipt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'user_id' => $this->faker->numberBetween(1, 10),
            'agent_id' => $this->faker->numberBetween(1, 10),
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'last_controlled_balance_history_id' => $this->faker->numberBetween(1, 10),
        ];
    }
}
