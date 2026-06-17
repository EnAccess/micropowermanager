<?php

namespace App\Services;

use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\PaymentInitiator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashTransactionService implements PaymentInitiator {
    public function __construct(
        private CashTransaction $cashTransaction,
        private Transaction $transaction,
    ) {}

    public function createTransaction(int $creatorId, float $amount, string $sender, string $message, string $type = Transaction::TYPE_DEFERRED_PAYMENT): Transaction {
        return DB::transaction(function () use ($creatorId, $amount, $sender, $message, $type) {
            $cashTransaction = $this->cashTransaction->newQuery()->create([
                'user_id' => $creatorId,
                'status' => 1,
            ]);

            $transaction = $this->transaction->newQuery()->make([
                'amount' => $amount,
                'sender' => $sender,
                'message' => $message,
                'type' => $type,
            ]);

            $transaction->originalTransaction()->associate($cashTransaction);
            $transaction->save();

            return $transaction;
        });
    }

    /**
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
     */
    public function initiatePayment(
        float $amount,
        string $sender,
        string $message,
        string $type,
        int $customerId,
        ?string $serialId = null,
    ): array {
        $creatorId = Auth::id();

        $transaction = $this->createTransaction($creatorId, $amount, $sender, $message, $type);

        return ['transaction' => $transaction, 'provider_data' => [], 'process_immediately' => true];
    }
}
