<?php

namespace App\Events;

use App\Models\Transaction\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MPM\Transaction\Provider\ITransactionProvider;

/**
 * TransactionSavedEvent.
 *
 * Dispatch this event when a transaction was saved. This will asynchronously
 * confirm the transaction.
 *
 * @property ITransactionProvider $transactionProvider The provider of this transaciton.
 */
class TransactionSavedEvent {
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ITransactionProvider $transactionProvider,
    ) {}
}
