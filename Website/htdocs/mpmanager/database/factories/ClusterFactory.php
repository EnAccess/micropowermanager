<?php

namespace Database\Factories;

use App\Models\Cluster;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClusterFactory extends Factory
{
    protected $model = Cluster::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'manager_id' => $this->faker->numberBetween(1, 10),
            'geo_data' => '{}',
        ];
    }
}
