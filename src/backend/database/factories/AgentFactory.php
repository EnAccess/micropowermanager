<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory {
    protected $model = Agent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'password' => '123456',
            'email' => $this->faker->unique()->safeEmail,
            'mobile_device_id' => '-',
            'fire_base_token' => '-',
            'balance' => 0,
            'commission_revenue' => 0,
            'due_to_energy_supplier' => 0,
            'connection' => 'tenant',
        ];
    }
}
