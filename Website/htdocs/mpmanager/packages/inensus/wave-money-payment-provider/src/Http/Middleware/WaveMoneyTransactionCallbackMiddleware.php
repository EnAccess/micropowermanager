<?php

namespace Inensus\WaveMoneyPaymentProvider\Http\Middleware;

use App\Jobs\ProcessPayment;
use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Inensus\WaveMoneyPaymentProvider\Http\Requests\TransactionCallbackRequest;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Data\TransactionCallbackData;
use Inensus\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;

class WaveMoneyTransactionCallbackMiddleware
{
    public function __construct(private WaveMoneyTransactionService $transactionService)
    {
    }

    public function handle(TransactionCallbackRequest $request, Closure $next)
    {
        $callbackData = $request->getMappedObject();

        try {
            $waveMoneyTransaction = $this->transactionService->getByOrderId($callbackData->getOrderId());

            if ($callbackData->mapTransactionStatus($callbackData->getStatus()) === TransactionCallbackData::STATUS_FAILURE) {
                $status = WaveMoneyTransaction::STATUS_FAILED;
            } else {
                // we set the transaction status as completed by wave money, but we don't process it yet
                $status = WaveMoneyTransaction::STATUS_COMPLETED_BY_WAVE_MONEY;
            }

            $request->attributes->add(['waveMoneyTransaction' => $waveMoneyTransaction]);
            $request->attributes->add(['status' => $status]);


            if (config('app.env') === 'production') {//production queue
                $queue = 'payment';
            } elseif (config('app.env') === 'staging') {
                $queue = 'staging_payment';
            } else { // local queue‚
                $queue = 'local_payment';
            }

            $transaction = $waveMoneyTransaction->transaction()->first();
            ProcessPayment::dispatch($transaction->id)->allOnConnection('redis')->onQueue($queue);
        } catch (\Exception $exception) {
            Log::critical('WaveMoney transaction callback called with wrong orderId ' . $callbackData->getOrderId());

            $data = collect([
                'success' => 0,
                'message' => $exception->getMessage()
            ]);
            return new Response($data, 400);
        }

        return $next($request);
    }
}