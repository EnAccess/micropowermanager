<?php
namespace Inensus\WaveMoneyPaymentProvider\Http\Middleware;

use Closure;
use http\Client\Request;
use Illuminate\Http\Response;

class WaveMoneyTransactionMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        $transactionProvider = resolve('WaveMoneyPaymentProvider');
        try {
            $transactionProvider->validateRequest($request);


        } catch (\Exception $exception) {
            $data = collect([
                'success' => 0,
                'message' => $exception->getMessage()
            ]);
            return new Response($data, 400);
        }

        $transactionProvider->setValidData($request);

        $transactionProvider->saveTransaction();

        $transaction = $transactionProvider->saveCommonData();
        $request->attributes->add(['transactionId' => $transaction->id]);
        $owner = $transaction->meter->meterParameter->owner;
        $request->attributes->add(['customerName' =>$owner->name.' '.$owner->surname]);
        return $next($request);
    }
}