<?php

namespace Database\Factories;

use App\Models\AgentBalanceHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentBalanceHistoryFactory extends Factory {
    protected $model = AgentBalanceHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'agent_id' => $this->faker->numberBetween(1, 10),
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'transaction_id' => $this->faker->numberBetween(1, 10),
            'available_balance' => $this->faker->randomFloat(2, 1, 100),
            'due_to_supplier' => $this->faker->randomFloat(2, 1, 100),
            'trigger_id' => $this->faker->numberBetween(1, 10),
            'trigger_type' => $this->faker->randomElement([
                'agent_charge',
                'agent_transaction',
                'agent_commission',
                'agent_appliance',
                'agent_receipt',
            ]),
        ];
    }
}
