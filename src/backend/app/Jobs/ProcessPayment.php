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
     * Create a new job instance.
     */
    public function __construct(int $companyId, protected int $transactionId) {
        $this->onConnection('redis');
        $this->onQueue('payment');

        $this->companyId = $companyId;

        parent::__construct($companyId);
    }

    /**
     * Execute the job.
     */
    public function executeJob(): void {
        TransactionPaymentProcessor::process($this->companyId, $this->transactionId);
    }
}
