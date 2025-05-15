<?php

namespace Inensus\StronMeter;

use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\MainSettings;
use App\Models\Token;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\StronMeter\Exceptions\StronApiResponseException;
use Inensus\StronMeter\Models\StronCredential;
use Inensus\StronMeter\Models\StronTransaction;

class StronMeterApi implements IManufacturerAPI {
    protected $api;
    private $rootUrl = '/vending/';

    public function __construct(
        Client $httpClient,
        private StronTransaction $stronTransaction,
        private MainSettings $mainSettings,
        private StronCredential $credentials,
    ) {
        $this->api = $httpClient;
    }

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $meter = $transactionContainer->device->device;
        $tariff = $transactionContainer->tariff;
        $owner = $transactionContainer->device->person;

        $transactionContainer->chargedEnergy += $transactionContainer->amount / $tariff->total_price;

        Log::debug('ENERGY TO BE CHARGED float '.(float) $transactionContainer->chargedEnergy.
            ' Manufacturer => StronMeterApi');

        $credentials = $this->credentials->newQuery()->firstOrFail();
        $mainSettings = $this->mainSettings->newQuery()->first();
        $postParams = [
            'CustomerId' => strval($owner->id),
            'MeterId' => $meter->serial_number,
            'Price' => strval($tariff->total_price),
            'Rate' => '1',
            'Amount' => $transactionContainer->amount,
            'AmountTmp' => $mainSettings ? $mainSettings->currency : 'USD',
            'Company' => $credentials->company_name,
            'Employee' => $credentials->username,
            'ApiToken' => $credentials->api_token,
        ];

        if (config('app.env') === 'demo' || config('app.env') === 'development') {
            // debug token for development
            $transactionResult = ['48725997619297311927'];
        } else {
            try {
                $response = $this->api->post(
                    $credentials->api_url.$this->rootUrl,
                    [
                        'body' => json_encode($postParams),
                        'headers' => [
                            'Content-Type' => 'application/json;charset=utf-8',
                        ],
                    ]
                );
                $transactionResult = explode(',', (string) $response->getBody());
            } catch (GuzzleException $gException) {
                Log::critical(
                    'Stron API Transaction Failed',
                    [
                        'URL :' => $this->rootUrl,
                        'Body :' => json_encode($postParams),
                        'message :' => $gException->getMessage(),
                    ]
                );
                throw new StronApiResponseException($gException->getMessage());
            }
        }

        $manufacturerTransaction = $this->stronTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'stron_transaction',
        ]);
        $token = $transactionResult[0];

        return [
            'token' => $token,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $transactionContainer->chargedEnergy,
        ];
    }

    public function clearDevice(Device $device) {
        // TODO: Implement clearDevice() method.
    }
}
