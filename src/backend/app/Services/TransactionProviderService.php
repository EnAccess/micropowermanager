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
        return $this->transaction->withAll('BelongsToMorph');
    }
}
