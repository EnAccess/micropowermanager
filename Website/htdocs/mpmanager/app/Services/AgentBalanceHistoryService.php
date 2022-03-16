<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCharge;
use App\Models\AgentCommission;
use App\Models\AgentReceipt;
use App\Models\Transaction\AgentTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgentBalanceHistoryService
{

    public function agentBalanceHistories(int $agentId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return AgentBalanceHistory::query()
            ->where('agent_id', $agentId)
            ->whereHasMorph(
                'trigger',
                '*'
            )->latest()->paginate();
    }

    /**
     * @param Agent $agent
     * @param array $data
     * @return Builder|Model
     */
    public function create(Agent $agent, array $data)
    {
        return AgentBalanceHistory::query()->create([
            'agent_id' => $agent->id,
            'amount' => $data['amount'],
            'type' => 'Charge',
            'available_balance' => $agent->balance
        ]);
    }
}
