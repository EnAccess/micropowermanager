<?php

namespace Database\Factories;

use App\Models\AgentSoldAppliance;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentSoldApplianceFactory extends Factory {
    protected $model = AgentSoldAppliance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'person_id' => $this->faker->randomNumber(),
            'agent_assigned_appliance_id' => $this->faker->randomNumber(),
        ];
    }
}
