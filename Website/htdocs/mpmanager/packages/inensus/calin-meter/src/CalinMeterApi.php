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
    protected $api;
    private $meterParameter;
    private $transaction;
    private $rootUrl = '/token/';
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
        $this->calinMeterApiRequests =$calinMeterApiRequests;
        $this->apiHelpers = $apiHelpers;
    }

    public function chargeMeter(TransactionDataContainer $transactionContainer): array
    {
        $meterParameter = $transactionContainer->meterParameter;
        $transactionContainer->chargedEnergy += $transactionContainer->amount /
            ($meterParameter->tariff->total_price / 100);

        Log::debug('ENERGY TO BE CHARGED float ' . (float)$transactionContainer->chargedEnergy .
            ' Manufacturer => CalinMeterApi');

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => (float)$transactionContainer->chargedEnergy,
            ];
        } else {

            $meter = $transactionContainer->meter;
            $credentials = $this->credentials->newQuery()->firstOrFail();
            $energy = 100;//(float)$transactionContainer->chargedEnergy;

            $timestamp = time();
            $cipherText = $this->apiHelpers->generateCipherText(
                $meter->id,
                $credentials->user_id,
                $meter->serial_number,
                'CreditToken',
                $energy,
                $timestamp,
                $credentials->api_key
            );
            $tokenParams = [
                'serial_id' => $meter->id,
                'user_id' => $credentials->user_id,
                'meter_id' => $meter->serial_number,
                'token_type' => 'CreditToken',
                'amount' => $energy,
                'timestamp' => $timestamp,
                'ciphertext' => $cipherText,
            ];

            $token = $this->calinMeterApiRequests->post($credentials->api_url.$this->rootUrl,$tokenParams);
            $this->associateManufacturerTransaction($transactionContainer);
            return [
                'token' => $token,
                'energy' => $energy
            ];
        }
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
        $manufacturerTransaction = $this->calinTransaction->newQuery()->create([
            'transaction_id' => $transactionContainer->transaction->id,
        ]);
        $transactionContainer->transaction->originalTransaction()->associate($manufacturerTransaction)->save();
    }
}
