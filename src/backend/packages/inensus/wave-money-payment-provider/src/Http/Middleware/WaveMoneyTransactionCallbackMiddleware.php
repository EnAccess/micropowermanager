<?php

namespace Inensus\WaveMoneyPaymentProvider\Http\Middleware;

use App\Jobs\ProcessPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Inensus\WaveMoneyPaymentProvider\Http\Requests\TransactionCallbackRequestMapper;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Data\TransactionCallbackData;
use Inensus\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;
use Inensus\WaveMoneyPaymentProvider\Providers\WaveMoneyTransactionProvider;

class WaveMoneyTransactionCallbackMiddleware {
    public function __construct(private WaveMoneyTransactionService $transactionService) {}

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
                    ProcessPayment::dispatch($companyId, $transaction->id);
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
