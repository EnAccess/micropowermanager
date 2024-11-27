<?php

namespace App\Services;

use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;

class CashTransactionService {
    private $cashTransaction;
    private $transaction;

    public function __construct(CashTransaction $cashTransaction, Transaction $transaction) {
        $this->transaction = $transaction;
        $this->cashTransaction = $cashTransaction;
    }

    public function createCashTransaction($creatorId, $amount, $sender, $deviceSerial = null) {
        $cashTransaction = $this->cashTransaction->newQuery()->create(
            [
                'user_id' => $creatorId,
                'status' => 1,
            ]
        );

        $transaction = $this->transaction->newQuery()->make(
            [
                'amount' => $amount,
                'sender' => $sender,
                'message' => $deviceSerial ?? '-',
                'type' => 'deferred_payment',
            ]
        );
        $transaction->originalTransaction()->associate($cashTransaction);
        $transaction->save();

        return $transaction;
    }
}
