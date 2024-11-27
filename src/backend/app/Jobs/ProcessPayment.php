<?php

namespace App\Jobs;

use App\Models\Transaction\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MPM\Transaction\TransactionPaymentProcessor;

class ProcessPayment extends AbstractJob {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var Transaction
     */
    protected $transactionId;

    /**
     * Create a new job instance.
     *
     * @param int $transaction_id
     */
    public function __construct(int $transactionId) {
        $this->transactionId = $transactionId;

        parent::__construct(get_class($this));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function executeJob(): void {
        TransactionPaymentProcessor::process($this->transactionId);
    }
}
