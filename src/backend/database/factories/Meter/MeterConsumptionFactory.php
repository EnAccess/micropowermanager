<?php

namespace Database\Factories\Meter;

use App\Models\Meter\MeterConsumption;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MeterConsumption> */
class MeterConsumptionFactory extends Factory {
    protected $model = MeterConsumption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'meter_id' => 1,
            'total_consumption' => $this->faker->randomFloat(2, 0, 100),
            'consumption' => $this->faker->randomFloat(2, 0, 10),
            'credit_on_meter' => $this->faker->randomFloat(2, 0, 5),
            'reading_date' => Carbon::now()->subDays(1),
        ];
    }
}
