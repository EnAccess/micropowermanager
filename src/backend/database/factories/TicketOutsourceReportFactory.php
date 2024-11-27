<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\Ticket\Models\TicketOutsourceReport;

class TicketOutsourceReportFactory extends Factory {
    protected $model = TicketOutsourceReport::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'date' => $this->faker->date(),
            'path' => $this->faker->url,
        ];
    }
}
