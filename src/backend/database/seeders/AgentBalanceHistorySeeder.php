<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use App\Models\Transaction\AgentTransaction;
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
            // Fetch all transactions for this agent
            $transactions = AgentTransaction::where('agent_id', $agent->id)->pluck('id')->toArray();

            // If no transaction exists, create a default one
            if (empty($transactions)) {
                $transaction = AgentTransaction::create([
                    'agent_id' => $agent->id,
                    'status' => 1,
                    'sender' => 'System',
                ]);
                $transactions = [$transaction->id];
            }

            for ($i = 0; $i < 30; ++$i) {
                AgentBalanceHistory::factory()->create([
                    'agent_id' => $agent->id,
                    'transaction_id' => fake()->randomElement($transactions),
                ]);
            }
        }
    }
}
