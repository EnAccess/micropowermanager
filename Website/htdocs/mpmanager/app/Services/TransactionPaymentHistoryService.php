<?php

namespace App\Services;

use App\Models\PaymentHistory;
use App\Models\Transaction\Transaction;

class TransactionPaymentHistoryService implements IAssignationService
{
    private Transaction $transaction;
    private PaymentHistory $paymentHistory;

    public function setAssigned($paymentHistory)
    {
        $this->paymentHistory = $paymentHistory;
    }

    public function setAssignee($transaction)
    {
        $this->transaction = $transaction;
    }

    public function assign()
    {
        $this->paymentHistory->transaction()->associate($this->transaction);

        return $this->paymentHistory;
    }
}
