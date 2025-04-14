<?php

namespace Database\Factories;

use App\Models\AgentAssignedAppliances;
use App\Utils\DemoCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentAssignedAppliancesFactory extends Factory {
    protected $model = AgentAssignedAppliances::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'agent_id' => $this->faker->numberBetween(1, 10),
            'appliance_id' => $this->faker->numberBetween(1, 10),
            'user_id' => $this->faker->numberBetween(1, 10),
            'cost' => $this->faker->randomFloat(2, 1, 10) * DemoCompany::DEMO_COMPANY_CURRENCY_FACTOR,
        ];
    }
}
