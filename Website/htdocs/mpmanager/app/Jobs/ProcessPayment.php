<?php

namespace App\Jobs;

use App\Misc\TransactionDataContainer;
use App\Models\Transaction\Transaction;
use App\PaymentHandler\AccessRate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use function config;

class ProcessPayment extends AbstractJob
{
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
    public function __construct(int $transactionId)
    {
        $this->transactionId = $transactionId;

        parent::__construct(get_class($this));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function executeJob(): void
    {
        EnergyTransactionProcessor::dispatch($this->transactionId)
            ->allOnConnection('redis')
            ->onQueue(config('services.queues.energy'));
    }
}
