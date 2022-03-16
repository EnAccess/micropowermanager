<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCharge;

class AgentChargeService
{

    public function create(Agent $agent, array $data)
    {
        $agentCharge = AgentCharge::query()->create(
            [
            'agent_id' => $agent->id,
            'user_id' => $data['user_id'],
            ]
        );
        $history = AgentBalanceHistory::query()->make(
            [
            'agent_id' => $agent->id,
            'amount' => request()->input('amount'),
            'available_balance' => $agent->balance,
            'due_to_supplier' => $agent->due_to_energy_supplier
            ]
        );
        $history->trigger()->associate($agentCharge);
        $history->save();
        return $agentCharge->fresh();
    }
}
