<?php

namespace Database\Factories;

use App\Models\Transaction\AgentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentTransactionFactory extends Factory {
    protected $model = AgentTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'agent_id' => 1,
            'mobile_device_id' => '123456789',
            'status' => 1,
            'manufacturer_transaction_type' => 'test',
            'manufacturer_transaction_id' => 1,
        ];
    }
}
