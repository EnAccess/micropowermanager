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
        try {
            $transaction = $this->swiftaTransactionService->getTransactionById(
                $request->integer('transaction_id')
            );
            $this->swiftaTransactionService->checkAmountIsSame($request->integer('amount'), $transaction);

            if (!is_int($request->attributes->get('companyId'))) {
                Log::warning('Swifta callback arrived without a company id', [
                    'transaction_id' => $transaction->id,
                ]);

                throw new \Exception('Company could not be resolved for this callback.');
            }
        } catch (\Exception $exception) {
            $response = collect([
                'success' => 0,
                'message' => $exception->getMessage(),
            ]);

            return new Response($response, 400);
        }

        $request->attributes->add([
            'transaction' => $transaction,
            'reference' => $request->string('transaction_reference')->toString(),
        ]);

        return $next($request);
    }
}
