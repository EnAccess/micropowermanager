<?php

namespace App\Services;

use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Facades\DB;

class CashTransactionService {
    public function __construct(private CashTransaction $cashTransaction, private Transaction $transaction) {}

    public function createCashTransaction(int $creatorId, float $amount, string $sender, ?string $deviceSerial = null, ?int $applianceId = null, string $type = Transaction::TYPE_DEFERRED_PAYMENT): Transaction {
        return DB::transaction(function () use ($creatorId, $amount, $sender, $deviceSerial, $applianceId, $type) {
            $cashTransaction = $this->cashTransaction->newQuery()->create([
                'user_id' => $creatorId,
                'status' => 1,
            ]);

            $transaction = $this->transaction->newQuery()->make([
                'amount' => $amount,
                'sender' => $sender,
                'message' => $deviceSerial ?? strval($applianceId ?? '-'),
                'type' => $type,
            ]);

            $transaction->originalTransaction()->associate($cashTransaction);
            $transaction->save();

            return $transaction;
        });
    }
}
