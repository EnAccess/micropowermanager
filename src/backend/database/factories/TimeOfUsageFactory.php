<?php

namespace Database\Factories;

use App\Models\TimeOfUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeOfUsageFactory extends Factory {
    protected $model = TimeOfUsage::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'tariff_id' => 1,
            'start' => '00:00',
            'end' => '01:00',
            'value' => $this->faker->randomFloat(2, 0, 10),
        ];
    }
}
