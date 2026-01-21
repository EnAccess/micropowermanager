<?php

namespace App\Plugins\MesombPaymentProvider\Http\Middleware;

use App\Plugins\MesombPaymentProvider\Exceptions\MesombPayerMustHaveOnlyOneConnectedMeterException;
use App\Plugins\MesombPaymentProvider\Exceptions\MesombPaymentPhoneNumberNotFoundException;
use App\Plugins\MesombPaymentProvider\Exceptions\MesombStatusFailedException;
use App\Plugins\MesombPaymentProvider\Providers\MesombTransactionProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MesombTransactionRequest {
    /**
     * @return Request|Response|JsonResponse
     */
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
