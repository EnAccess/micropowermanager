<?php

namespace Database\Factories;

use App\Models\Meter\MeterToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeterTokenFactory extends Factory
{
    protected $model = MeterToken::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'transaction_id' => 1,
            'meter_id' => 1,
            'token' => '123456789',
            'energy' => 0.123,
        ];
    }
}
