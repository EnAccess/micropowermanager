<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\WaveMoneyPaymentProvider\Http\Requests\TransactionInitializeRequest;
use Inensus\WaveMoneyPaymentProvider\Http\Resources\WaveMoneyResource;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\WaveMoneyApiService;
use Inensus\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;

class WaveMoneyController extends Controller {
    public function __construct(
        private WaveMoneyTransactionService $transactionService,
        private WaveMoneyApiService $apiService,
    ) {}

    public function startTransaction(TransactionInitializeRequest $request): WaveMoneyResource {
        $transaction = $request->get('waveMoneyTransaction');

        return WaveMoneyResource::make($this->apiService->requestPayment($transaction));
    }

    public function transactionCallBack(Request $request) {
        $transaction = $request->get('waveMoneyTransaction');
        $status = $request->get('status');

        $this->transactionService->update($transaction, [
            'status' => $status,
            'attempts' => $transaction->attempts + 1,
        ]);
    }
}
