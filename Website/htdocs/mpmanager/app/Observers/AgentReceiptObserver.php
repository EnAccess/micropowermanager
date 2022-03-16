<?php

namespace App\Observers;

use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use App\Models\AgentReceipt;
use App\Models\AgentReceiptDetail;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use mysql_xdevapi\Exception;

class AgentReceiptObserver
{
    private $agentBalanceHistory;

    public function __construct(AgentBalanceHistory $agentBalanceHistory)
    {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function created(AgentReceipt $receipt): void
    {
        $agentId = $receipt->agent_id;
        $agent = Agent::query()->find($agentId);
        $due = $agent->due_to_energy_supplier;
        $sinceLastVisit = 0;
        $lastBalanceHistory = AgentReceipt::query()->where('agent_id', $agentId)
            ->latest()
            ->skip(1)
            ->take(1)
            ->get();
        if (count($lastBalanceHistory) > 0) {
            $sinceLastVisit = AgentBalanceHistory::query()
                ->where('agent_id', $agentId)
                ->where('id', '>', $lastBalanceHistory[0]->last_controlled_balance_history_id)
                ->whereIn('trigger_type', ['agent_appliance', 'agent_transaction'])
                ->sum('amount');
        }
        try {
            $earlier = AgentReceiptDetail::query()->select('summary')
                ->whereHas(
                    'receipt',
                    static function ($q) use ($agentId) {
                        $q->where('agent_id', $agentId);
                    }
                )->latest()->firstOrFail()->summary;
        } catch (ModelNotFoundException $exception) {
            $earlier = 0;
        }

        $summary = $receipt->amount - $agent->due_to_energy_supplier;
        $collected = $receipt->amount;

        AgentReceiptDetail::query()->create(
            [
            'agent_receipt_id' => $receipt->id,
            'due' => $due ?? 0,
            'collected' => $collected ?? 0,
            'since_last_visit' => $sinceLastVisit ?? 0,
            'earlier' => $earlier ?? 0,
            'summary' => $summary ?? 0

            ]
        );


        $history = AgentBalanceHistory::query()->make(
            [
            'agent_id' => $agent->id,
            'amount' => $receipt->amount,
            'transaction_id' => $receipt->id,
            'available_balance' => $agent->balance,
            'due_to_supplier' => $agent->due_to_energy_supplier
            ]
        );
        $history->trigger()->associate($receipt);
        $history->save();
    }
}
