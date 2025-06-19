<?php

namespace Database\Factories;

use App\Models\Target;
use Illuminate\Database\Eloquent\Factories\Factory;

class TargetFactory extends Factory {
    protected $model = Target::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        $ownerType = $this->faker->randomElement(['mini-grid', 'cluster']);

        return [
            'target_date' => $this->faker->date('Y-m-d'),
            'type' => $ownerType,
            'owner_type' => $ownerType,
            'owner_id' => $ownerType === 'cluster'
                ? $this->faker->numberBetween(1, 2)
                : $this->faker->numberBetween(1, 5),
        ];
    }
}
