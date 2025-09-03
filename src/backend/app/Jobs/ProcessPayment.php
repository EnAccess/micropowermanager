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

    protected int $transactionId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $companyId, int $transactionId) {
        $this->onConnection('redis');
        $this->onQueue('payment');

        $this->companyId = $companyId;
        $this->transactionId = $transactionId;

        parent::__construct($companyId);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function executeJob(): void {
        TransactionPaymentProcessor::process($this->companyId, $this->transactionId);
    }
}
