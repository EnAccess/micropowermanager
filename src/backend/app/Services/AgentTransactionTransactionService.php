<?php

namespace App\Services;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<Transaction, AgentTransaction>
 */
class AgentTransactionTransactionService implements IAssignationService {
    private Transaction $transaction;
    private AgentTransaction $agentTransaction;

    public function setAssigned($transaction): void {
        $this->transaction = $transaction;
    }

    public function setAssignee($agentTransaction): void {
        $this->agentTransaction = $agentTransaction;
    }

    public function assign(): Transaction {
        $this->transaction->originalTransaction()->associate($this->agentTransaction);

        return $this->transaction;
    }
}
