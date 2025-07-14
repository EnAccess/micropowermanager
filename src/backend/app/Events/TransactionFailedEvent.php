<?php

namespace App\Events;

use App\Models\Transaction\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * TransactionFailedEvent.
 *
 * Dispatch this event to asynchronously handle a failed transaction.
 *
 * @property Transaction $transaction The transaction related to this payment.
 * @property string|null $message     Optional error message. Can be null.
 */
class TransactionFailedEvent {
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public ?string $message,
    ) {}
}
