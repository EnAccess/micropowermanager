<?php

namespace Inensus\MesombPaymentProvider\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inensus\MesombPaymentProvider\Exceptions\MesombPayerMustHaveOnlyOneConnectedMeterException;
use Inensus\MesombPaymentProvider\Exceptions\MesombPaymentPhoneNumberNotFoundException;
use Inensus\MesombPaymentProvider\Exceptions\MesombStatusFailedException;
use Inensus\MesombPaymentProvider\Providers\MesombTransactionProvider;

class MesombTransactionRequest {
    public function handle(Request $request, \Closure $next) {
        $transactionProvider = resolve(MesombTransactionProvider::class);
        try {
            $transactionProvider->validateRequest($request);
        } catch (MesombStatusFailedException) {
            Log::warning(
                'Status of Payment is Failed',
                [
                    'message' => $request->input('message'),
                    'pk' => $request->input('pk'),
                ]
            );

            return response()->json([
                'errors' => [
                    'code' => 400,
                    'title' => 'Mesomp Status Failed.',
                    'detail' => $request->input('message'),
                ],
            ], 400);
        } catch (MesombPaymentPhoneNumberNotFoundException|MesombPayerMustHaveOnlyOneConnectedMeterException $exception) {
            Log::critical(
                'Transaction Validation Failed',
                [
                    'message' => $exception->getMessage(),
                    'pk' => $request->input('pk'),
                ]
            );

            return response()->json([
                'errors' => [
                    'code' => 422,
                    'title' => 'Mesomp Payment Failed.',
                    'detail' => $exception->getMessage(),
                ],
            ], 422);
        }

        return $next($request);
    }
}
