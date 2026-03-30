<?php

declare(strict_types=1);

namespace App\Plugins\WaveMoneyPaymentProvider\Http\Controllers;

use App\Plugins\WaveMoneyPaymentProvider\Http\Requests\TransactionInitializeRequest;
use App\Plugins\WaveMoneyPaymentProvider\Http\Resources\WaveMoneyResource;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Api\WaveMoneyApiService;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WaveMoneyController extends Controller {
    public function __construct(
        private WaveMoneyTransactionService $transactionService,
        private WaveMoneyApiService $apiService,
    ) {}

    public function startTransaction(TransactionInitializeRequest $request): WaveMoneyResource {
        $transaction = $request->get('waveMoneyTransaction');

        return WaveMoneyResource::make($this->apiService->requestPayment($transaction));
    }

    public function transactionCallBack(Request $request): void {
        $transaction = $request->get('waveMoneyTransaction');
        $status = $request->get('status');

        $this->transactionService->update($transaction, [
            'status' => $status,
            'attempts' => $transaction->attempts + 1,
        ]);
    }
}
