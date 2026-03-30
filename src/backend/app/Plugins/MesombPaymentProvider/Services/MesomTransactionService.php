<?php

namespace App\Plugins\MesombPaymentProvider\Services;

use App\Models\Transaction\Transaction;
use App\Plugins\MesombPaymentProvider\Models\MesombTransaction;

class MesomTransactionService {
    public function __construct(private MesombTransaction $mesombTransaction, private Transaction $transaction) {}

    /**
     * @param array<string, mixed> $data
     */
    public function assignIncomingDataToMesombTransaction(array $data): MesombTransaction {
        return $this->mesombTransaction->newQuery()->create([
            'pk' => $data['pk'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'b_party' => $data['b_party'],
            'message' => $data['message'],
            'service' => $data['service'],
            'ts' => $data['ts'],
            'direction' => $data['direction'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function assignIncomingDataToTransaction(array $data): Transaction {
        return $this->transaction->newQuery()->make([
            'amount' => (int) $data['amount'],
            'sender' => $data['b_party'],
            'message' => $data['meter'],
            'type' => 'energy',
            'original_transaction_type' => 'mesomb_transaction',
        ]);
    }

    public function associateMesombTransactionWithTransaction(
        MesombTransaction $mesombTransaction,
        Transaction $transaction,
    ): Transaction {
        return $mesombTransaction->transaction()->save($transaction);
    }
}
