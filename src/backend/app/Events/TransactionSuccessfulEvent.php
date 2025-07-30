<?php

namespace App\Events;

use App\Models\Transaction\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * TransactionSuccessfulEvent.
 *
 * Dispatch this event when a transaction was successful.
 *
 * @property Transaction $transaction The transaction that was successful.
 */
class TransactionSuccessfulEvent {
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Transaction $transaction,
    ) {}
}
