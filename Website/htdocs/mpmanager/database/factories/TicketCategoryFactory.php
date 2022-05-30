<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\Ticket\Models\TicketCategory;

class TicketCategoryFactory extends Factory
{
    protected $model = TicketCategory::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'label_name'=>$this->faker->word,
            'label_color'=>$this->faker->colorName,
        ];
    }
}
