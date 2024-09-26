<?php

namespace Database\Factories;

use App\Models\Transaction\VodacomTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class VodacomTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VodacomTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'conversation_id' => $this->faker->unique()->randomNumber(),
            'originator_conversation_id' => $this->faker->unique(true)->randomNumber(),
            'mpesa_receipt' => $this->faker->name(),
            'transaction_date' => $this->faker->dateTime(),
            'transaction_id' => $this->faker->unique()->randomNumber(),
            'status' => 0,
        ];
    }
}
