<?php

namespace Inensus\SwiftaPaymentProvider\Http\Middleware;

use App\Jobs\ProcessPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inensus\SwiftaPaymentProvider\Services\SwiftaTransactionService;

class SwiftaTransactionCallbackMiddleware {
    public function __construct(private SwiftaTransactionService $swiftaTransactionService) {}

    public function handle(Request $request, \Closure $next) {
        try {
            $transactionId = $request->input('transaction_id');
            $amount = $request->input('amount');
            $transactionReference = $request->input('transaction_reference');
            $transaction = $this->swiftaTransactionService->getTransactionById($transactionId);
            $this->swiftaTransactionService->checkAmountIsSame($amount, $transaction);
            $request->attributes->add(['transaction' => $transaction]);
            $request->attributes->add(['reference' => $transactionReference]);

            ProcessPayment::dispatch($transaction->id)
                ->allOnConnection('redis')
                ->onQueue(config('services.queues.payment'));
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
