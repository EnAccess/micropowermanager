<?php

namespace Database\Factories;

use App\Models\SHSToken;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class SHSTokenFactory extends Factory {
    protected $model = SHSToken::class;

    public function definition(): array {
        return [
            'token' => $this->generateToken(),
            'token_type' => SHSToken::TYPE_TIME,
            'token_amount' => $this->faker->numberBetween(1, 30), // Random number of days
            'transaction_id' => null,
            'device_id' => null,
        ];
    }

    public function timeBased(): self {
        return $this->state(fn (array $attributes) => [
            'token_type' => SHSToken::TYPE_TIME,
        ]);
    }

    public function energyBased(): self {
        return $this->state(fn (array $attributes) => [
            'token_type' => SHSToken::TYPE_ENERGY,
        ]);
    }

    public function forTransaction(Transaction $transaction): self {
        return $this->state(fn (array $attributes) => [
            'transaction_id' => $transaction->id,
            'device_id' => $transaction->device->id,
        ]);
    }

    public function withAmount(float $amount): self {
        return $this->state(fn (array $attributes) => [
            'token_amount' => ceil($amount / 100), // Convert amount to days
        ]);
    }

    protected function generateToken(): string {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }
}
