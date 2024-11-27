<?php

namespace App\Services;

use App\Models\AgentBalanceHistory;
use App\Models\AgentReceipt;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<AgentBalanceHistory, AgentReceipt>
 */
class AgentReceiptHistoryBalanceService implements IAssignationService {
    private AgentBalanceHistory $agentBalanceHistory;
    private AgentReceipt $agentReceipt;

    public function setAssigned($agentBalanceHistory): void {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function setAssignee($agentReceipt): void {
        $this->agentReceipt = $agentReceipt;
    }

    public function assign(): AgentBalanceHistory {
        $this->agentBalanceHistory->trigger()->associate($this->agentReceipt);

        return $this->agentBalanceHistory;
    }
}
