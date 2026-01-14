<?php

namespace App\Plugins\SwiftaPaymentProvider\Http\Controllers;

use App\Plugins\SwiftaPaymentProvider\Http\Requests\SwiftaTransactionRequest;
use App\Plugins\SwiftaPaymentProvider\Http\Requests\SwiftaValidationRequest;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaTransaction;
use App\Plugins\SwiftaPaymentProvider\Services\SwiftaTransactionService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SwiftaPaymentProviderController extends Controller {
    public function __construct(private SwiftaTransactionService $swiftaTransactionService) {}

    public function validation(SwiftaValidationRequest $request): Response {
        $transactionId = $request->get('transactionId');
        $customerName = $request->get('customerName');
        $data = collect([
            'success' => 1,
            'amount' => $request->input('amount'),
            'cipher' => $request->input('cipher'),
            'timestamp' => $request->input('timestamp'),
            'transaction_id' => $transactionId,
            'customer' => $customerName,
        ]);

        return new Response($data, 200);
    }

    public function transaction(SwiftaTransactionRequest $request): Response {
        $transaction = $request->get('transaction');
        $reference = $request->get('reference');
        $swiftaTransaction = $transaction->originalTransaction()->first();
        $updateData = [
            'status' => SwiftaTransaction::STATUS_PENDING,
            'transaction_reference' => $reference,
        ];

        $this->swiftaTransactionService->update($swiftaTransaction, $updateData);

        $data = collect(
            [
                'success' => 1,
                'amount' => $request->input('amount'),
                'cipher' => $request->input('cipher'),
                'timestamp' => $request->input('timestamp'),
                'transaction_id' => $request->input('transaction_id'),
            ]
        );

        return new Response($data, 201);
    }
}
