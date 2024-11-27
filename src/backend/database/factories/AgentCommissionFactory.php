<?php

namespace Database\Factories;

use App\Models\AgentCommission;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentCommissionFactory extends Factory {
    protected $model = AgentCommission::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'name' => 'Sample Commission',
            'energy_commission' => 0.05,
            'appliance_commission' => 0.05,
            'risk_balance' => -10000,
        ];
    }
}
