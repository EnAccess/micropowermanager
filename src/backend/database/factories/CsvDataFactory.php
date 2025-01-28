<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\BulkRegistration\Models\CsvData;

class CsvDataFactory extends Factory {
    protected $model = CsvData::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'user_id' => $this->faker->randomNumber(1, 10),
            'csv_filename' => 'bulk_registration'.$this->faker->randomNumber(1, 4).'csv',
            'csv_data' => '',
        ];
    }
}
