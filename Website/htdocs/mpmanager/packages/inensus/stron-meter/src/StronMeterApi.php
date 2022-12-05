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
        $transactionContainer->chargedEnergy += $transactionContainer->amount /
            ($meterParameter->tariff()->first()->total_price);

        Log::debug('ENERGY TO BE CHARGED float ' . (float)$transactionContainer->chargedEnergy .
            ' Manufacturer => StronMeterApi');


        $meter = $transactionContainer->meter;
        $credentials = $this->credentials->newQuery()->firstOrFail();
        $mainSettings = $this->mainSettings->newQuery()->first();
        $postParams = [
            "CustomerId" => strval($meterParameter->owner->id),
            "MeterId" => $meter->serial_number,
            "Price" => strval($meterParameter->tariff->total_price),
            "Rate" => "1",
            "Amount" => $transactionContainer->amount,
            "AmountTmp" => $mainSettings ? $mainSettings->currency : 'USD',
            "Company" => $credentials->company_name,
            "Employee" => $credentials->username,
            "ApiToken" => $credentials->api_token
        ];

        if (config('app.env') === 'local' || config('app.env') === 'development') {
            //debug token for development
            $transactionResult = ['48725997619297311927'];
        } else {
            try {

                $response = $this->api->post(
                    $credentials->api_url . $this->rootUrl,
                    [
                        'body' => json_encode($postParams),
                        'headers' => [
                            'Content-Type' => 'application/json;charset=utf-8',
                        ],
                    ]
                );
                $transactionResult = explode(",", (string)$response->getBody());
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
        }

        $manufacturerTransaction = $this->stronTransaction->newQuery()->create();
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'stron_transaction'
        ]);
        $token = $transactionResult[0];

        return [
            'token' => $token,
            'energy' => $transactionContainer->chargedEnergy
        ];

    }

    public function clearMeter(Meter $meter)
    {
        // TODO: Implement clearMeter() method.
    }

}
