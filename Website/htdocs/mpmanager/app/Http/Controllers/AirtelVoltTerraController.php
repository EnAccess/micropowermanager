<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPayment;

//WorkAround for only VOLTTERRA
class AirtelVoltTerraController extends Controller
{
    public function store($meterSerial, $amount)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'response');
        try {
            $provider = resolve('AirtelVoltTerra');
            $request = ['meterSerial' => $meterSerial, 'amount' => $amount];
            $provider->validateRequest($request);
            $provider->saveTransaction();
            $transaction = $provider->saveCommonData();
            event('transaction.saved', $provider);
            ProcessPayment::dispatch($transaction->id)->allOnConnection('redis')
                ->onQueue(config('services.queues.payment'));

            $jsonData = [
                'message' => 'transaction process started',
                'transactionId' => $transaction->id,
            ];

        } catch (\Exception $exception) {
            $jsonData = [
                'message' => 'transaction process can not be started',
                'transactionId' => null,
                'error' => $exception->getMessage(),
            ];
        }
        $jsonString = json_encode($jsonData);
        file_put_contents($tempFile, $jsonString);
        return response()->file($tempFile);
    }
}
