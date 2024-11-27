<?php

namespace App\Services;

use App\Models\AgentBalanceHistory;
use App\Models\AgentCharge;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<AgentBalanceHistory, AgentCharge>
 */
class AgentChargeHistoryBalanceService implements IAssignationService {
    private AgentBalanceHistory $agentBalanceHistory;
    private AgentCharge $agentCharge;

    public function setAssigned($agentBalanceHistory): void {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function setAssignee($agentCharge): void {
        $this->agentCharge = $agentCharge;
    }

    public function assign(): AgentBalanceHistory {
        $this->agentBalanceHistory->trigger()->associate($this->agentCharge);

        return $this->agentBalanceHistory;
    }
}
