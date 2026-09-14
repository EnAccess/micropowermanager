<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Http\Middleware;

use App\Plugins\WaveMoneyPaymentProvider\Http\Requests\TransactionCallbackRequestMapper;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;
use App\Plugins\WaveMoneyPaymentProvider\Providers\WaveMoneyTransactionProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WaveMoneyTransactionCallbackMiddleware {
    public function __construct(private WaveMoneyTransactionService $transactionService) {}

    /**
     * @return Request|Response
     */
    public function handle(Request $request, \Closure $next) {
        $mapper = new TransactionCallbackRequestMapper();
        $callbackData = $mapper->getMappedObject($request);

        if (!is_int($request->attributes->get('companyId'))) {
            Log::warning('Wave Money callback arrived without a company id', [
                'order_id' => $callbackData->orderId,
            ]);

            return new Response(collect([
                'success' => 0,
                'message' => 'Company could not be resolved for this callback.',
            ]), 400);
        }

        try {
            $waveMoneyTransaction = $this->transactionService->getByOrderId($callbackData->orderId);

            resolve(WaveMoneyTransactionProvider::class)->init($waveMoneyTransaction);
        } catch (\Exception $exception) {
            Log::critical('WaveMoney transaction callback called with wrong orderId '.$callbackData->orderId);

            return new Response(collect([
                'success' => 0,
                'message' => $exception->getMessage(),
            ]), 400);
        }

        $request->attributes->add([
            'waveMoneyTransaction' => $waveMoneyTransaction,
            'callbackData' => $callbackData,
        ]);

        return $next($request);
    }
}
