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
        private WaveMoneyTransactionService $transactionService)
    {
    }

    public function startTransaction($slug,TransactionInitializeRequest $request)
    {
        $meterSerial = $request->input('meterSerial');
        $amount = $request->input('amount');
        $companyId = $request->input('slug');
        $this->transactionService->initializeTransactionRequest(strval($meterSerial), floatval($amount), $companyId);
    }

    public function transactionCallBack(TransactionCallbackRequest $request)
    {
        $data = $request->getMappedObject();

    }
}
