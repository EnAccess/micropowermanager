<?php

namespace App\Observers;

use App\Models\AgentAssignedAppliances;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCharge;
use App\Models\AgentCommission;
use App\Models\AgentReceipt;
use App\Models\Transaction\AgentTransaction;
use App\Services\AgentService;

class AgentBalanceHistoryObserver {
    public function __construct(
        private AgentService $agentService,
    ) {}

    /**
     * Handle the asset person "updated" event.
     *
     * @param AgentBalanceHistory $agentBalanceHistory
     *
     * @return void
     */
    public function created(AgentBalanceHistory $agentBalanceHistory) {
        $trigger = $agentBalanceHistory->trigger()->first();
        $agent = $this->agentService->getById($agentBalanceHistory->agent_id);

        if ($trigger instanceof AgentAssignedAppliances || $trigger instanceof AgentTransaction) {
            if ($agent->balance < 0) {
                $agent->due_to_energy_supplier += (-1 * $agentBalanceHistory->amount);
                $agent->balance += $agentBalanceHistory->amount;
            } else {
                if ($agent->balance < (-1 * $agentBalanceHistory->amount)) {
                    $agent->due_to_energy_supplier += -1 * ($agent->balance + $agentBalanceHistory->amount);
                    $agent->balance += $agentBalanceHistory->amount;
                } else {
                    $agent->balance += $agentBalanceHistory->amount;
                }
            }

            $agent->update();
        } elseif ($trigger instanceof AgentCommission) {
            $agent->commission_revenue += $agentBalanceHistory->amount;

            if ($agent->balance < 0) {
                $agent->due_to_energy_supplier += (-1 * $agentBalanceHistory->amount);
            }

            $agent->update();
        } elseif ($trigger instanceof AgentCharge) {
            $agent->balance += $agentBalanceHistory->amount;
            $agent->update();
        } elseif ($trigger instanceof AgentReceipt) {
            $agent->due_to_energy_supplier -= $agentBalanceHistory->amount;
            $agent->balance += $agentBalanceHistory->amount + $agent->commission_revenue;
            $agent->commission_revenue = 0;
            $agent->update();
        }
    }
}
