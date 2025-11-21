<?php

namespace Database\Factories\Inensus\Ticket\Models;

use App\Models\Ticket\TicketOutsourcePayoutReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TicketOutsourcePayoutReport> */
class TicketOutsourcePayoutReportFactory extends Factory {
    protected $model = TicketOutsourcePayoutReport::class;

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
