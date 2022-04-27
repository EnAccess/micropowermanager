<?php

namespace Database\Factories;

use App\Models\MiniGrid;
use Illuminate\Database\Eloquent\Factories\Factory;

class MiniGridFactory extends Factory
{
    protected $model = MiniGrid::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'cluster_id' => $this->faker->numberBetween(1, 10),
        ];
    }
}
