<?php

namespace App\Services;

use App\Models\AgentBalanceHistory;
use App\Models\AgentCommission;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<AgentBalanceHistory, AgentCommission>
 */
class AgentCommissionHistoryBalanceService implements IAssignationService {
    private AgentBalanceHistory $agentBalanceHistory;
    private AgentCommission $agentCommission;

    public function setAssigned($agentBalanceHistory): void {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function setAssignee($agentCommission): void {
        $this->agentCommission = $agentCommission;
    }

    public function assign(): AgentBalanceHistory {
        $this->agentBalanceHistory->trigger()->associate($this->agentCommission);

        return $this->agentBalanceHistory;
    }
}
