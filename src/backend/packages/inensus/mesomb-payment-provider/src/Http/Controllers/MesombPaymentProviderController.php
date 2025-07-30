<?php

namespace Inensus\MesombPaymentProvider\Http\Controllers;

use App\Events\TransactionSavedEvent;
use App\Jobs\ProcessPayment;
use Illuminate\Routing\Controller;
use Inensus\MesombPaymentProvider\Http\Resources\MesombTransactionProcessingResource;

class MesombPaymentProviderController extends Controller {
    public function store() {
        $transactionProvider = resolve('MesombPaymentProvider');
        $transactionProvider->saveTransaction();
        // store common data
        $transaction = $transactionProvider->saveCommonData();
        // fire TransactionSavedEvent to confirm the transaction
        event(new TransactionSavedEvent($transactionProvider));

        if (config('app.env') === 'production') {// production queue
            $queue = 'payment';
        } elseif (config('app.env') === 'demo') {
            $queue = 'staging_payment';
        } else { // local queue‚
            $queue = 'local_payment';
        }
        ProcessPayment::dispatch($transaction->id)->allOnConnection('redis')->onQueue($queue);

        return new MesombTransactionProcessingResource($transaction->originalTransaction()->first());
    }
}
