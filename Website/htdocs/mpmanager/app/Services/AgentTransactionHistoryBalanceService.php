<?php

namespace App\Services;

use App\Models\AgentBalanceHistory;
use App\Models\Transaction\AgentTransaction;

class AgentTransactionHistoryBalanceService implements IAssignationService
{
    private AgentTransaction $agentTransaction;
    private AgentBalanceHistory $agentBalanceHistory;

    public function setAssigned($agentBalanceHistory)
    {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function setAssignee($agentTransaction)
    {
        $this->agentTransaction = $agentTransaction;
    }

    public function assign()
    {
        $this->agentBalanceHistory->trigger()->associate($this->agentTransaction);

        return $this->agentBalanceHistory;
    }
}
