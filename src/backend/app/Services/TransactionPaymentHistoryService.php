<?php

namespace App\Services;

use App\Models\PaymentHistory;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<PaymentHistory, Transaction>
 */
class TransactionPaymentHistoryService implements IAssignationService {
    private PaymentHistory $paymentHistory;
    private Transaction $transaction;

    public function setAssigned($paymentHistory): void {
        $this->paymentHistory = $paymentHistory;
    }

    public function setAssignee($transaction): void {
        $this->transaction = $transaction;
    }

    public function assign(): PaymentHistory {
        $this->paymentHistory->transaction()->associate($this->transaction);

        return $this->paymentHistory;
    }
}
