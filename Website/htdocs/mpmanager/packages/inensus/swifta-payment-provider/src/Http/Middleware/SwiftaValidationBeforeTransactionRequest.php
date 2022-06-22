<?php

namespace Inensus\SwiftaPaymentProvider\Http\Middleware;

use App\Models\Meter\Meter;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Inensus\SwiftaPaymentProvider\Http\Exceptions\SwiftaValidationFailedException;


class SwiftaValidationBeforeTransactionRequest
{
    private $meter;

    public function __construct(Meter $meter)
    {
        $this->meter = $meter;
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            $this->checkMeterNumberExists($request);
        } catch (\Exception $exception) {
            $response = collect([
                'success' => 0,
                'message' => $exception->getMessage()
            ]);
            return new Response($response, 400);
        }

        $transactionProvider = resolve('SwiftaPaymentProvider');
        try {
            $transactionProvider->validateRequest($request);
        } catch (\Exception $exception) {
            Log::warning('Swifta Transaction Validation Failed', [
                'message' => $exception->getMessage()
            ]);
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

    private function checkMeterNumberExists(Request $request)
    {
        try {
            $this->meter->newQuery()->where('serial_number', $request->input('meter_number'))->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            Log::warning('Swifta Transaction Validation Failed', [
                'message' => 'meter_number validation field.'
            ]);
            throw  new \Exception('meter_number validation field.');
        }

    }

}