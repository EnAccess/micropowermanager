<?php

namespace App\Services;

use App\Models\AgentAssignedAppliances;
use App\Models\AgentBalanceHistory;

class AgentAssignedApplianceHistoryBalanceService implements IAssignationService
{
    private AgentAssignedAppliances $agentAssignedAppliance;
    private AgentBalanceHistory $agentBalanceHistory;

    public function setAssigned($agentBalanceHistory)
    {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function setAssignee($agentAssignedAppliance)
    {
        $this->agentAssignedAppliance = $agentAssignedAppliance;
    }

    public function assign()
    {
        $this->agentBalanceHistory->trigger()->associate($this->agentAssignedAppliance);

        return $this->agentBalanceHistory;
    }
}
