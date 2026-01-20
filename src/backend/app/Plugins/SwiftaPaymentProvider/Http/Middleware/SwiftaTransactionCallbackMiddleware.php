<?php

namespace App\Plugins\SwiftaPaymentProvider\Http\Middleware;

use App\Jobs\ProcessPayment;
use App\Plugins\SwiftaPaymentProvider\Services\SwiftaTransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SwiftaTransactionCallbackMiddleware {
    public function __construct(private SwiftaTransactionService $swiftaTransactionService) {}

    /**
     * @return Request|Response
     */
    public function handle(Request $request, \Closure $next) {
        try {
            $transactionId = $request->input('transaction_id');
            $amount = $request->input('amount');
            $transactionReference = $request->input('transaction_reference');
            $transaction = $this->swiftaTransactionService->getTransactionById($transactionId);
            $this->swiftaTransactionService->checkAmountIsSame($amount, $transaction);
            $request->attributes->add(['transaction' => $transaction]);
            $request->attributes->add(['reference' => $transactionReference]);
            $companyId = $request->attributes->get('companyId') ?? null;
            if ($companyId !== null) {
                dispatch(new ProcessPayment($companyId, $transaction->id));
            } else {
                Log::warning('Company ID not found in request attributes. Payment transaction job not triggered for transaction '.$transaction->id);
            }
        } catch (\Exception $exception) {
            $response = collect([
                'success' => 0,
                'message' => $exception->getMessage(),
            ]);

            return new Response($response, 400);
        }

        return $next($request);
    }
}
