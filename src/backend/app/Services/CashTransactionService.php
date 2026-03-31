<?php

namespace App\Services;

use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\PaymentInitializer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashTransactionService implements PaymentInitializer {
    public function __construct(private CashTransaction $cashTransaction, private Transaction $transaction) {}

    public function createTransaction(int $creatorId, float $amount, string $sender, string $message, string $type): Transaction {
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
     * @return array{transaction: Transaction, provider_data: array<string, mixed>}
     */
    public function initializePayment(
        float $amount,
        string $sender,
        string $message,
        string $type,
        int $customerId,
        ?string $serialId = null,
    ): array {
        $creatorId = Auth::id();

        $transaction = $this->createTransaction($creatorId, $amount, $sender, $message, $type);

        return ['transaction' => $transaction, 'provider_data' => []];
    }
}
