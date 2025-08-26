<?php

namespace Inensus\MesombPaymentProvider\Http\Controllers;

use App\Events\TransactionSavedEvent;
use App\Jobs\ProcessPayment;
use Illuminate\Routing\Controller;
use Inensus\MesombPaymentProvider\Http\Resources\MesombTransactionProcessingResource;
use Inensus\MesombPaymentProvider\Providers\MesombTransactionProvider;

class MesombPaymentProviderController extends Controller {
    public function store() {
        /** @var MesombTransactionProvider */
        $transactionProvider = resolve('MesombPaymentProvider');
        $transactionProvider->saveTransaction();
        // store common data
        $transaction = $transactionProvider->saveCommonData();
        // fire TransactionSavedEvent to confirm the transaction
        event(new TransactionSavedEvent($transactionProvider));

        ProcessPayment::dispatch($transaction->id);

        return new MesombTransactionProcessingResource($transaction->originalTransaction()->first());
    }
}
