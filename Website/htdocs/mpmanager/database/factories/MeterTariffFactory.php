<?php

namespace Database\Factories;

use App\Models\Meter\MeterTariff;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeterTariffFactory extends Factory
{
    protected $model = MeterTariff::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'price' => 100000,
            'total_price' => 100000,
            'currency' => 'EUR',
            'factor' => $this->faker->numberBetween(0,5),
        ];
    }
}
