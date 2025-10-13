<?php

namespace Inensus\WaveMoneyPaymentProvider\Http\Middleware;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inensus\WaveMoneyPaymentProvider\Providers\WaveMoneyTransactionProvider;

class WaveMoneyTransactionMiddleware {
    /**
     * @return Request|Response
     */
    public function handle(Request $request, \Closure $next) {
        $transactionProvider = resolve(WaveMoneyTransactionProvider::class);

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

        $waveMoneyTransaction = $transactionProvider->getSubTransaction();
        $request->attributes->add(['waveMoneyTransaction' => $waveMoneyTransaction]);

        return $next($request);
    }
}
