<?php

namespace Database\Factories;

use App\Models\Target;
use Illuminate\Database\Eloquent\Factories\Factory;

class TargetFactory extends Factory
{
    protected $model = Target::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'target_date' => $this->faker->date('Y-m-d'),
            'type' => $this->faker->randomElement(['mini-grid','cluster']),
            'owner_type' => $this->faker->randomElement(['mini-grid','cluster']),
            'owner_id' => $this->faker->numberBetween(1,10),
        ];
    }
}
