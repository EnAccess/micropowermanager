<?php

namespace Inensus\MesombPaymentProvider\Http\Controllers;

use App\Events\TransactionSavedEvent;
use App\Jobs\ProcessPayment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Inensus\MesombPaymentProvider\Http\Resources\MesombTransactionProcessingResource;
use Inensus\MesombPaymentProvider\Providers\MesombTransactionProvider;

class MesombPaymentProviderController extends Controller {
    public function store(Request $request): MesombTransactionProcessingResource {
        $transactionProvider = resolve(MesombTransactionProvider::class);
        $transactionProvider->saveTransaction();
        // store common data
        $transaction = $transactionProvider->saveCommonData();
        // fire TransactionSavedEvent to confirm the transaction
        event(new TransactionSavedEvent($transactionProvider));

        $companyId = $request->attributes->get('companyId') ?? null;
        if ($companyId !== null) {
            dispatch(new ProcessPayment($companyId, $transaction->id));
        } else {
            Log::warning('Company ID not found in request attributes. Payment transaction job not triggered for transaction '.$transaction->id);
        }

        return new MesombTransactionProcessingResource($transaction->originalTransaction()->first());
    }
}
