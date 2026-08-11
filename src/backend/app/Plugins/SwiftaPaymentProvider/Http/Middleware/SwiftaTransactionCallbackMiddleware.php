<?php

namespace App\Plugins\SwiftaPaymentProvider\Http\Middleware;

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
        if (!is_int($request->attributes->get('companyId'))) {
            Log::warning('Swifta callback arrived without a company id', [
                'transaction_id' => $request->integer('transaction_id'),
            ]);

            return new Response(collect([
                'success' => 0,
                'message' => 'Company could not be resolved for this callback.',
            ]), 400);
        }

        $transaction = $this->swiftaTransactionService->getTransactionById(
            $request->integer('transaction_id')
        );
        $this->swiftaTransactionService->checkAmountIsSame($request->integer('amount'), $transaction);

        $request->attributes->add([
            'transaction' => $transaction,
            'reference' => $request->string('transaction_reference')->toString(),
        ]);

        return $next($request);
    }
}
