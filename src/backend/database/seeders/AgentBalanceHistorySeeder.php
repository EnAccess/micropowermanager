<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class AgentBalanceHistorySeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionDemoCompany();
    }

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
