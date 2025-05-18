<?php

namespace App\Services;

use App\Models\Transaction\Transaction;

class TransactionProviderService {
    /**
     * TransactionProviderService constructor.
     */
    public function __construct() {}

    public function getTransactionProviders() {
        $types = Transaction::whereHas('originalTransaction')
            ->distinct()
            ->pluck('original_transaction_type');

        return $types;
    }
}
