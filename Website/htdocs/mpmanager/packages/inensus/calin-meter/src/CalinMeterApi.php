<?php

namespace Inensus\CalinMeter;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\Transaction;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Inensus\CalinMeter\Helpers\ApiHelpers;
use Inensus\CalinMeter\Http\Requests\CalinMeterApiRequests;
use Inensus\CalinMeter\Models\CalinCredential;
use Inensus\CalinMeter\Models\CalinTransaction;


class CalinMeterApi implements IManufacturerAPI
{
    const CREDIT_TOKEN = 'CreditToken';
    protected $api;
    private $meterParameter;
    private $transaction;
    private $rootUrl = '/tokennew';
    private $calinTransaction;
    private $mainSettings;
    private $credentials;
    private $calinMeterApiRequests;
    private $apiHelpers;

    public function __construct(
        Client $httpClient,
        MeterParameter $meterParameter,
        CalinTransaction $calinTransaction,
        Transaction $transaction,
        MainSettings $mainSettings,
        CalinCredential $credentials,
        CalinMeterApiRequests $calinMeterApiRequests,
        ApiHelpers $apiHelpers
    ) {
        $this->api = $httpClient;
        $this->meterParameter = $meterParameter;
        $this->calinTransaction = $calinTransaction;
        $this->transaction = $transaction;
        $this->mainSettings = $mainSettings;
        $this->credentials = $credentials;
        $this->calinMeterApiRequests = $calinMeterApiRequests;
        $this->apiHelpers = $apiHelpers;
    }

    public function chargeMeter(TransactionDataContainer $transactionContainer): array
    {
        $meterParameter = $transactionContainer->meterParameter;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / ($meterParameter->tariff->total_price);

        Log::debug('ENERGY TO BE CHARGED float ' . (float)$transactionContainer->chargedEnergy .
            ' Manufacturer => CalinMeterApi');

        $meter = $transactionContainer->meter;
        $credentials = $this->credentials->newQuery()->firstOrFail();
        $energy = (float)$transactionContainer->chargedEnergy;

        $tokenParams = [
            'user_id' => $credentials->user_id,
            'password' => $credentials->api_key,
            'meter_id' => $meter->serial_number,
            'token_type' => self::CREDIT_TOKEN,
            'amount' => $energy,
        ];

        Log::debug('TOKEN PARAMS ', $tokenParams);
        $url = $credentials->api_url . $this->rootUrl;
        if (config('app.env') === 'local' || config('app.env') === 'development') {
            //debug token for development
            $token = '48725997619297311927';
        } else {
            $token = $this->calinMeterApiRequests->post($url, $tokenParams);
        }

        $this->associateManufacturerTransaction($transactionContainer);

        return [
            'token' => $token,
            'energy' => $energy
        ];

    }

    /**
     * @param Meter $meters
     * @return void
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearMeter(Meter $meters)
    {
        throw  new ApiCallDoesNotSupportedException('This api call does not supported');
    }

    /**
     * @param TransactionDataContainer $transactionContainer
     * @return void
     */
    public function associateManufacturerTransaction(TransactionDataContainer $transactionContainer): void
    {
        Log::debug('ASSOCIATE MANUFACTURER TRANSACTION CALLED');
        $manufacturerTransaction = $this->calinTransaction->newQuery()->create();
        Log::debug($manufacturerTransaction);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => get_class($manufacturerTransaction)
        ])->save();
        Log::debug($transactionContainer->transaction->originalTransaction()->first());
    }
}
