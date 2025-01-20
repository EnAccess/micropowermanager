<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use Illuminate\Database\Seeder;

class AgentBalanceHistorySeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $agents = Agent::all();

        foreach ($agents as $agent) {
            AgentBalanceHistory::factory()->create(['agent_id' => $agent->id]);
        }
    }
}
