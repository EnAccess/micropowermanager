<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'person_id' => $this->faker->numberBetween(1, 10),
            'password' => '123456',
            'email' => $this->faker->unique()->safeEmail,
            'mini_grid_id' => $this->faker->numberBetween(1, 10),
            'agent_commission_id' => $this->faker->numberBetween(1, 10),
            'device_id' => '-',
            'fire_base_token' => '-',
            'balance' => 0,
            'commission_revenue' => 0,
            'due_to_energy_supplier' => 0,
            'connection' => 'test_company_db',
        ];
    }
}
