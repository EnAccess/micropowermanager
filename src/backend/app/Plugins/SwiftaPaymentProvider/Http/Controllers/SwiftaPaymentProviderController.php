<?php

namespace App\Plugins\SwiftaPaymentProvider\Http\Controllers;

use App\Models\Transaction\Transaction;
use App\Plugins\SwiftaPaymentProvider\Http\Requests\SwiftaTransactionRequest;
use App\Plugins\SwiftaPaymentProvider\Http\Requests\SwiftaValidationRequest;
use App\Plugins\SwiftaPaymentProvider\Services\SwiftaTransactionService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

#[Group('Plugins / Swifta', 'API endpoints for integrating with Swifta payment services')]
class SwiftaPaymentProviderController extends Controller {
    public function __construct(private SwiftaTransactionService $swiftaTransactionService) {}

    public function validation(SwiftaValidationRequest $request): Response {
        // SwiftaTransactionMiddleware resolves both onto the request attributes; they are not
        // request input, so they cannot be read with input()/string().
        $transactionId = $request->attributes->getInt('transactionId');
        $customerName = $request->attributes->getString('customerName');
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
        /** @var Transaction $transaction */
        $transaction = $request->attributes->get('transaction');

        $this->swiftaTransactionService->applyCallback(
            $transaction,
            $request->attributes->getString('reference'),
            $request->attributes->getInt('companyId'),
        );

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
