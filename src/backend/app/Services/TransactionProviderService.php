<?php

namespace App\Services;

use App\Models\Transaction\Transaction;

class TransactionProviderService {
    private $transaction;

    /**
     * TransactionProviderService constructor.
     *
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction) {
        $this->transaction = $transaction;
    }

    public function getTransactionProviders() {
        $types = Transaction::whereHas('originalTransaction')
            ->distinct()
            ->pluck('original_transaction_type');

        return $types;
    }
}
