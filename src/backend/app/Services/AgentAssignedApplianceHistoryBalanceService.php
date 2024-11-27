<?php

namespace App\Services;

use App\Models\AgentAssignedAppliances;
use App\Models\AgentBalanceHistory;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<AgentBalanceHistory, AgentAssignedAppliances>
 */
class AgentAssignedApplianceHistoryBalanceService implements IAssignationService {
    private AgentBalanceHistory $agentBalanceHistory;
    private AgentAssignedAppliances $agentAssignedAppliance;

    public function setAssigned($agentBalanceHistory): void {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function setAssignee($agentAssignedAppliance): void {
        $this->agentAssignedAppliance = $agentAssignedAppliance;
    }

    public function assign(): AgentBalanceHistory {
        $this->agentBalanceHistory->trigger()->associate($this->agentAssignedAppliance);

        return $this->agentBalanceHistory;
    }
}
