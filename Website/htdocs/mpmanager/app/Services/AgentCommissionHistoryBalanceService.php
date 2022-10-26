<?php

namespace App\Services;

use App\Models\AgentBalanceHistory;
use App\Models\AgentCommission;

class AgentCommissionHistoryBalanceService implements IAssignationService
{
    private AgentCommission $agentCommission;
    private AgentBalanceHistory $agentBalanceHistory;


    public function setAssigned($agentBalanceHistory)
    {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function setAssigner($agentCommission)
    {
        $this->agentCommission = $agentCommission;
    }

    public function assign()
    {
        $this->agentBalanceHistory->trigger()->associate($this->agentCommission);

        return $this->agentBalanceHistory;
    }
}
