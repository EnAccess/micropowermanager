<?php


declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\WaveMoneyPaymentProvider\Http\Requests\TransactionCallbackRequest;
use Inensus\WaveMoneyPaymentProvider\Http\Requests\TransactionInitializeRequest;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\WaveMoneyApiService;
use Inensus\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;

class WaveMoneyController extends Controller
{

    public function __construct(
        private WaveMoneyTransactionService $transactionService,
        private WaveMoneyApiService $apiService)
    {
    }

    public function startTransaction(TransactionInitializeRequest $request)
    {
        $this->transactionService->initializeTransactionRequest($request->getMeterSerial());
    }

    public function transactionCallBack(TransactionCallbackRequest $request)
    {
        $data = $request->getMappedObject();

    }
}
