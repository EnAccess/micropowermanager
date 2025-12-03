<?php

namespace Database\Factories\Ticket;

use App\Models\Ticket\TicketCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'out_source' => $this->faker->randomElement([0, 1]),
        ];
    }
}
