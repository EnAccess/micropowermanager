<?php

namespace App\Services;

use App\Models\AgentBalanceHistory;
use App\Models\AgentCharge;

class AgentChargeHistoryBalanceService implements IAssignationService
{
    private AgentCharge $agentCharge;
    private AgentBalanceHistory $agentBalanceHistory;

    public function setAssigned($agentBalanceHistory)
    {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function setAssigner($agentCharge)
    {
        $this->agentCharge = $agentCharge;
    }

    public function assign()
    {
        $this->agentBalanceHistory->trigger()->associate($this->agentCharge);

        return $this->agentBalanceHistory;
    }
}