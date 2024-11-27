<?php

namespace App\Services;

use App\Models\AgentBalanceHistory;
use App\Models\Transaction\AgentTransaction;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<AgentBalanceHistory, AgentTransaction>
 */
class AgentTransactionHistoryBalanceService implements IAssignationService {
    private AgentBalanceHistory $agentBalanceHistory;
    private AgentTransaction $agentTransaction;

    public function setAssigned($agentBalanceHistory): void {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function setAssignee($agentTransaction): void {
        $this->agentTransaction = $agentTransaction;
    }

    public function assign(): AgentBalanceHistory {
        $this->agentBalanceHistory->trigger()->associate($this->agentTransaction);

        return $this->agentBalanceHistory;
    }
}
