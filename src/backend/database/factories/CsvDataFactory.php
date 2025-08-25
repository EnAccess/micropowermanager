<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\BulkRegistration\Models\CsvData;

/** @extends Factory<CsvData> */
class CsvDataFactory extends Factory {
    protected $model = CsvData::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'user_id' => $this->faker->randomNumber(10),
            'csv_filename' => 'bulk_registration'.$this->faker->randomNumber(4).'csv',
            'csv_data' => '',
        ];
    }
}
