<?php

namespace Database\Factories;

use App\Models\PaymentHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentHistoryFactory extends Factory {
    protected $model = PaymentHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'id' => 1,
            'transaction_id' => 1,
            'amount' => $this->faker->randomFloat(2, 0, 100),
            'payment_service' => 'agent_transaction',
            'sender' => $this->faker->phoneNumber,
            'payment_type' => $this->faker->randomElement(['appliance', 'energy', 'installment', 'access rate']),
            'paid_for_type' => $this->faker->randomElement(['appliance', 'token', 'loan_rate', 'access_rate']),
            'paid_for_id' => 1,
            'payer_type' => 'person',
            'payer_id' => 1,
        ];
    }
}
