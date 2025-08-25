<?php

namespace Database\Factories\Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\Ticket\Models\TicketCategory;

/** @extends Factory<TicketCategory> */
class TicketCategoryFactory extends Factory {
    protected $model = TicketCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'label_name' => $this->faker->word,
            'label_color' => $this->faker->colorName,
            'out_source' => $this->faker->numberBetween(1, 10),
        ];
    }
}
