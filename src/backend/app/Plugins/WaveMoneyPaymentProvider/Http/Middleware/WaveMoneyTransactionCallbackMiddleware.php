<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Http\Middleware;

use App\Jobs\ProcessPayment;
use App\Plugins\WaveMoneyPaymentProvider\Http\Requests\TransactionCallbackRequestMapper;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Api\Data\TransactionCallbackData;
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
        $companyId = $request->attributes->get('companyId') ?? null;

        try {
            $waveMoneyTransaction = $this->transactionService->getByOrderId($callbackData->getOrderId());

            if ($callbackData->mapTransactionStatus($callbackData->getStatus()) ===
                TransactionCallbackData::STATUS_FAILURE) {
                $status = WaveMoneyTransaction::STATUS_FAILED;
            } else {
                // we set the transaction status as completed by wave money, but we don't process it yet
                $status = WaveMoneyTransaction::STATUS_COMPLETED_BY_WAVE_MONEY;
            }
            $transactionProvider = resolve(WaveMoneyTransactionProvider::class);
            $transactionProvider->init($waveMoneyTransaction);

            $request->attributes->add(['waveMoneyTransaction' => $waveMoneyTransaction]);
            $request->attributes->add(['status' => $status]);

            if ($status === WaveMoneyTransaction::STATUS_COMPLETED_BY_WAVE_MONEY) {
                // we process the transaction in the background
                $transaction = $waveMoneyTransaction->transaction()->first();
                if ($companyId !== null) {
                    dispatch(new ProcessPayment($companyId, $transaction->id));
                } else {
                    Log::warning('Company ID not found in request attributes. Payment transaction job not triggered for transaction '.$transaction->id);
                }
            }
        } catch (\Exception $exception) {
            Log::critical('WaveMoney transaction callback called with wrong orderId '.$callbackData->getOrderId());

            $data = collect([
                'success' => 0,
                'message' => $exception->getMessage(),
            ]);

            return new Response($data, 400);
        }

        return $next($request);
    }
}
