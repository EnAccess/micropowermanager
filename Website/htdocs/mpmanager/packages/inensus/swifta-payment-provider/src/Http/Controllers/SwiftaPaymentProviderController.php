<?php

namespace Inensus\SwiftaPaymentProvider\Http\Controllers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Inensus\SwiftaPaymentProvider\Http\Requests\SwiftaTransactionRequest;
use Inensus\SwiftaPaymentProvider\Http\Requests\SwiftaValidationRequest;
use Illuminate\Http\Response;
use Inensus\SwiftaPaymentProvider\Services\SwiftaTransactionService;

class SwiftaPaymentProviderController extends Controller
{
    private $swiftaTransactionService;

    public function __construct(SwiftaTransactionService $swiftaTransactionService)
    {
        $this->swiftaTransactionService = $swiftaTransactionService;
    }

    public function validation(SwiftaValidationRequest $request)
    {

        $transactionId = $request->get('transactionId');
        $customerName = $request->get('customerName');
        $data = collect([
            'success' => 1,
            'amount' => $request->input('amount'),
            'cipher' => $request->input('cipher'),
            'timestamp' => $request->input('timestamp'),
            'transaction_id' => $transactionId,
            'customer' => $customerName
        ]);
        return new Response($data, 200);
    }

    public function transaction(SwiftaTransactionRequest $request)
    {

        $transaction = $request->get('transaction');
        $this->swiftaTransactionService->setStatusPending($transaction);
        $data = collect(
            [
                'success' => 1,
                'amount' => $request->input('amount'),
                'cipher' => $request->input('cipher'),
                'timestamp' => $request->input('timestamp'),
                'transaction_id' => $request->input('transaction_id')
            ]
        );
        return new Response($data, 201);
    }
}