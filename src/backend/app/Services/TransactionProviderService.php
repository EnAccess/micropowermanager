<?php

namespace App\Services;

use App\Models\Transaction\Transaction;
use Illuminate\Support\Collection;

class TransactionProviderService {
    /**
     * TransactionProviderService constructor.
     */
    public function __construct() {}

    /**
     * @return Collection<int, string>
     */
    public function getTransactionProviders(): Collection {
        return Transaction::whereHas('originalTransaction')
            ->distinct()
            ->pluck('original_transaction_type');
    }
}
