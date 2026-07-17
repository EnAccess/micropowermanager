<?php

namespace App\Observers;

use App\Models\AgentBalanceHistory;
use App\Models\AgentCommission;
use App\Services\AgentService;

class AgentBalanceHistoryObserver {
    public function __construct(
        private AgentService $agentService,
    ) {}

    public function created(AgentBalanceHistory $agentBalanceHistory): void {
        $trigger = $agentBalanceHistory->trigger()->first();
        $agent = $this->agentService->getById($agentBalanceHistory->agent_id);

        if ($trigger instanceof AgentCommission) {
            // Commission ledger: accruals are positive, payouts (at receipt time) negative.
            $agent->commission_revenue += $agentBalanceHistory->amount;
        } else {
            // Balance ledger: sales (AgentTransaction/AgentAssignedAppliances, negative),
            // charges and receipts (positive). due_to_energy_supplier mirrors how far
            // the balance sits below zero, so it moves by the below-zero delta only.
            $oldBalance = $agent->balance;
            $agent->balance += $agentBalanceHistory->amount;
            $agent->due_to_energy_supplier += min($oldBalance, 0) - min($agent->balance, 0);
        }

        $agent->update();

        $agentBalanceHistory->available_balance = $agent->balance;
        $agentBalanceHistory->due_to_supplier = $agent->due_to_energy_supplier;
        $agentBalanceHistory->saveQuietly();
    }
}
