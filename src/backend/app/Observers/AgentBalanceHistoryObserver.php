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
            // Balance ledger: a single signed number for the company money the agent
            // holds. Sales and charges add; transfers (receipts) subtract.
            $agent->balance += $agentBalanceHistory->amount;
        }

        $agent->update();

        $agentBalanceHistory->available_balance = $agent->balance;
        $agentBalanceHistory->saveQuietly();
    }
}
