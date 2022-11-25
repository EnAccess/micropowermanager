<?php

namespace App\Jobs;

use App\Misc\TransactionDataContainer;
use App\Models\Transaction\Transaction;
use App\PaymentHandler\AccessRate;
use Exception;
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
    protected $transactionID;

    /**
     * Create a new job instance.
     *
     * @param int $transaction_id
     */
    public function __construct(int $transaction_id)
    {
        $this->transactionID = $transaction_id;
        parent::__construct(get_class($this));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function executeJob(): void
    {
        $transaction = Transaction::find($this->transactionID);
        EnergyTransactionProcessor::dispatch($transaction)
            ->allOnConnection('database')
            ->onQueue(config('services.queues.energy'));
    }
}
