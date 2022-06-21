<?php

namespace Inensus\StronMeter;

use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\Transaction;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\StronMeter\Exceptions\StronApiResponseException;
use Inensus\StronMeter\Models\StronCredential;
use Inensus\StronMeter\Models\StronTransaction;

class StronMeterApi implements IManufacturerAPI
{
    protected $api;
    private $meterParameter;
    private $transaction;
    private $rootUrl = '/vending/';
    private $stronTransaction;
    private $mainSettings;
    private $credentials;

    public function __construct(
        Client $httpClient,
        MeterParameter $meterParameter,
        StronTransaction $stronTransaction,
        Transaction $transaction,
        MainSettings $mainSettings,
        StronCredential $credentials
    ) {
        $this->api = $httpClient;
        $this->meterParameter = $meterParameter;
        $this->stronTransaction = $stronTransaction;
        $this->transaction = $transaction;
        $this->mainSettings = $mainSettings;
        $this->credentials = $credentials;
    }

    public function chargeMeter(TransactionDataContainer $transactionContainer): array
    {
        $meterParameter = $transactionContainer->meterParameter;
        $transactionContainer->chargedEnergy += $transactionContainer->amount
            / ($meterParameter->tariff()->first()->total_price / 100);

        Log::debug('ENERGY TO BE CHARGED float ' . (float)$transactionContainer->chargedEnergy .
            ' Manufacturer => StronMeterApi');

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => (float)$transactionContainer->chargedEnergy,
            ];
        } else {
            $meter = $transactionContainer->meter;
            $credentials = $this->credentials->newQuery()->firstOrFail();
            $mainSettings = $this->mainSettings->newQuery()->first();
            $postParams = [
                "CustomerId" => strval($meterParameter->owner->id),
                "MeterId" => $meter->serial_number,
                "Price" => strval($meterParameter->tariff->total_price / 100),
                "Rate" => "1",
                "Amount" => $transactionContainer->amount,
                "AmountTmp" => $mainSettings ? $mainSettings->currency : 'USD',
                "Company" => $credentials->company_name,
                "Employee" => $credentials->username,
                "ApiToken" => $credentials->api_token
            ];
            try {
                $request = $this->api->post(
                    $credentials->api_url . $this->rootUrl,
                    [
                        'body' => json_encode($postParams),
                        'headers' => [
                            'Content-Type' => 'application/json;charset=utf-8',
                        ],
                    ]
                );
                $transactionResult = explode(",", (string)$request->getBody());
            } catch (GuzzleException $gException) {
                Log::critical(
                    'Stron API Transaction Failed',
                    [
                        'URL :' => $this->rootUrl,
                        'Body :' => json_encode($postParams),
                        'message :' => $gException->getMessage()
                    ]
                );
                throw new StronApiResponseException($gException->getMessage());
            }
            $this->associateManufacturerTransaction($transactionContainer, $transactionResult);
            $token = $transactionResult[0];
            return [
                'token' => $token,
                'energy' => $transactionContainer->chargedEnergy
            ];
        }
    }

    public function clearMeter(Meter $meter)
    {
        // TODO: Implement clearMeter() method.
    }

    public function associateManufacturerTransaction(
        TransactionDataContainer $transactionContainer,
        $transactionResult = []
    ) {
            $manufacturerTransaction = $this->stronTransaction->newQuery()->create([
                'transaction_id' => $transactionContainer->transaction->id,
            ]);
        $transactionContainer->transaction->originalTransaction()->associate($manufacturerTransaction)->save();
    }
}
