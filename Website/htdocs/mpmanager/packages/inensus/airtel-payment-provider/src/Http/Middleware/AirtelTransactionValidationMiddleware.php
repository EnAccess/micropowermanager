<?php

namespace Inensus\AirtelPaymentProvider\Http\Middleware;

use Illuminate\Http\Response;
use Closure;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

class AirtelTransactionValidationMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        $transactionProvider = resolve('AirtelPaymentProvider');
        try {
            $transactionProvider->validateRequest($request->getContent());
            $transactionProvider->saveTransaction();

            return $next($request);
        } catch (\Exception $exception) {
            $xmlResponse =
                '<?xml version="1.0" encoding="UTF-8"?>' .
                '<COMMAND>' .
                '<STATUS>400</STATUS>' .
                '<MESSAGE>' . $exception->getMessage() . '</MESSAGE>' .
                '</COMMAND>';

            echo $xmlResponse;
            return false;
        }

    }
}