<?php

namespace App\Services;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;

class AgentTransactionTransactionService implements IAssignationService
{
    private AgentTransaction $agentTransaction;
    private Transaction $transaction;


    public function setAssigned($transaction)
    {
        $this->transaction = $transaction;
    }

    public function setAssignee($agentTransaction)
    {
        $this->agentTransaction = $agentTransaction;
    }

    public function assign()
    {
        $this->transaction->originalTransaction()->associate($this->agentTransaction);
        ;

        return $this->transaction;
    }
}
