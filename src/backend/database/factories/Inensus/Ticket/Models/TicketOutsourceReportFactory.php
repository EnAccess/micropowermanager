<?php

namespace Database\Factories\Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\Ticket\Models\TicketOutsourceReport;

/** @extends Factory<TicketOutsourceReport> */
class TicketOutsourceReportFactory extends Factory {
    protected $model = TicketOutsourceReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'date' => $this->faker->date(),
            'path' => $this->faker->url,
        ];
    }
}
