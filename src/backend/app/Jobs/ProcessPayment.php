<?php

namespace App\Jobs;

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
     * @var int
     */
    protected $transactionId;

    /**
     * Create a new job instance.
     *
     * @param int $transactionId
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
