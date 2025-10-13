<?php

namespace App\Services;

use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;

class CashTransactionService {
    public function __construct(private CashTransaction $cashTransaction, private Transaction $transaction) {}

    public function createCashTransaction(int $creatorId, float $amount, string $sender, ?string $deviceSerial = null): Transaction {
        $cashTransaction = $this->cashTransaction->newQuery()->create([
            'user_id' => $creatorId,
            'status' => 1,
        ]);

        $transaction = $this->transaction->newQuery()->make([
            'amount' => $amount,
            'sender' => $sender,
            'message' => $deviceSerial ?? '-',
            'type' => 'deferred_payment',
        ]);

        $transaction->originalTransaction()->associate($cashTransaction);
        $transaction->save();

        return $transaction;
    }
}
