<?php

namespace App\Services;

use App\Models\AgentBalanceHistory;
use App\Models\AgentReceipt;

class AgentReceiptHistoryBalanceService implements IAssignationService
{
    private AgentReceipt $agentReceipt;
    private AgentBalanceHistory $agentBalanceHistory;

    public function setAssigned($agentBalanceHistory)
    {
        $this->agentBalanceHistory = $agentBalanceHistory;
    }

    public function setAssigner($agentReceipt)
    {
        $this->agentReceipt = $agentReceipt;
    }

    public function assign()
    {
        $this->agentBalanceHistory->trigger()->associate($this->agentReceipt);

        return $this->agentBalanceHistory;
    }
}
