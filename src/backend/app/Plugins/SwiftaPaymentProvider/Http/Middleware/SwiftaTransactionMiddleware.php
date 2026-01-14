<?php

namespace App\Plugins\SwiftaPaymentProvider\Http\Middleware;

use App\Plugins\SwiftaPaymentProvider\Providers\SwiftaTransactionProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SwiftaTransactionMiddleware {
    /**
     * @return Request|Response
     */
    public function handle(Request $request, \Closure $next) {
        $transactionProvider = resolve(SwiftaTransactionProvider::class);

        try {
            $transactionProvider->validateRequest($request);
            $transactionProvider->saveTransaction();
        } catch (\Exception $exception) {
            $data = collect([
                'success' => 0,
                'message' => $exception->getMessage(),
            ]);

            return new Response($data, 400);
        }

        $swiftaTransaction = $transactionProvider->getSubTransaction();
        $transaction = $swiftaTransaction->transaction()->first();
        $owner = $transaction->device->person;

        $request->attributes->add(['transactionId' => $transaction->id]);
        $request->attributes->add(['customerName' => $owner->name.' '.$owner->surname]);

        return $next($request);
    }
}
