<?php

namespace Inensus\MesombPaymentProvider\Services;

use App\Models\Transaction\Transaction;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;

class MesomTransactionService {
    private $mesombTransaction;
    private $transaction;

    public function __construct(
        MesombTransaction $mesombTransaction,
        Transaction $transaction,
    ) {
        $this->mesombTransaction = $mesombTransaction;
        $this->transaction = $transaction;
    }

    public function assignIncomingDataToMesombTransaction(array $data) {
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

    public function assignIncomingDataToTransaction(array $data) {
        return $this->transaction->newQuery()->make([
            'amount' => (int) $data['amount'],
            'sender' => $data['b_party'],
            'message' => $data['meter'],
            'type' => 'energy',
            'original_transaction_type' => 'mesomb_transaction',
        ]);
    }

    public function associateMesombTransactionWithTransaction($mesombTransaction, $transaction) {
        return $mesombTransaction->transaction()->save($transaction);
    }
}
