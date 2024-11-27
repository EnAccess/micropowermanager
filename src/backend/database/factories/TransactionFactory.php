<?php

namespace Database\Factories;

use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory {
    protected $model = Transaction::class;

    public function definition(): array {
        return [
            'id' => $this->faker->unique()->randomNumber(),
            'amount' => $this->faker->unique()->randomNumber(),
            'sender' => $this->faker->phoneNumber,
            'message' => '47000268748',
        ];
    }
}
