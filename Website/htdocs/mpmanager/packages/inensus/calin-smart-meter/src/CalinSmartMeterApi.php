<?php

namespace Inensus\CalinSmartMeter;

use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\Transaction;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Inensus\CalinSmartMeter\Exceptions\CalinSmartCreadentialsNotFoundException;
use Inensus\CalinSmartMeter\Helpers\ApiHelpers;
use Inensus\CalinSmartMeter\Http\Requests\CalinSmartMeterApiRequests;
use Inensus\CalinSmartMeter\Models\CalinSmartCredential;
use Inensus\CalinSmartMeter\Models\CalinSmartTransaction;

class CalinSmartMeterApi implements IManufacturerAPI
{
    protected $api;
    private $meterParameter;
    private $transaction;
    private $rootUrl = '/POS_Purchase/';
    private $calinSmartTransaction;
    private $mainSettings;
    private $credentials;
    private $calinSmartMeterApiRequests;
    private $apiHelpers;

    public function __construct(
        Client $httpClient,
        MeterParameter $meterParameter,
        CalinSmartTransaction $calinSmartTransaction,
        Transaction $transaction,
        MainSettings $mainSettings,
        CalinSmartCredential $credentials,
        CalinSmartMeterApiRequests $calinSmartMeterApiRequests,
        ApiHelpers $apiHelpers
    ) {
        $this->api = $httpClient;
        $this->meterParameter = $meterParameter;
        $this->calinSmartTransaction = $calinSmartTransaction;
        $this->transaction = $transaction;
        $this->mainSettings = $mainSettings;
        $this->credentials = $credentials;
        $this->calinSmartMeterApiRequests = $calinSmartMeterApiRequests;
        $this->apiHelpers = $apiHelpers;
    }

    public function chargeMeter($transactionContainer): array
    {
        $meterParameter = $transactionContainer->meterParameter;
        $transactionContainer->chargedEnergy += $transactionContainer->amount /
            ($meterParameter->tariff()->first()->total_price);
        Log::critical('ENERGY TO BE CHARGED float ' . (float)$transactionContainer->chargedEnergy .
            ' Manufacturer => Calin Smart');


        $meter = $transactionContainer->meter;
        $energy = (float)$transactionContainer->chargedEnergy;
        try {
            $credentials = $this->credentials->newQuery()->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new CalinSmartCreadentialsNotFoundException($e->getMessage());
        }
        $url = $credentials->api_url . $this->rootUrl;
        $tokenParams = [
            'company_name' => $credentials->company_name,
            'user_name' => $credentials->user_name,
            'password' => $credentials->password,
            'password_vend' => $credentials->password_vend,
            'meter_number' => $meter->serial_number,
            'is_vend_by_unit' => true,
            'amount' => $energy
        ];
        if (config('app.env') === 'local' || config('app.env') === 'development') {
            //debug token for development
            $token = '48725997619297311927';
        } else {
            $token = $this->calinSmartMeterApiRequests->post($url, $tokenParams);
        }


        $manufacturerTransaction = $this->calinSmartTransaction->newQuery()->create();
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'calin_smart_transaction'
        ]);

        return [
            'token' => $token,
            'energy' => $energy
        ];

    }

    /**
     * @param Meter $meter
     *
     * @throws GuzzleException
     * @psalm-return array{result_code: mixed}
     */
    public function clearMeter(Meter $meter)
    {
        $root = '/Maintenance_ClearCredit/';
        try {
            $credentials = $this->credentials->newQuery()->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new CalinSmartCreadentialsNotFoundException($e->getMessage());
        }
        $url = $credentials->api_url . $root;
        $tokenParams = [
            'company_name' => $credentials->company_name,
            'user_name' => $credentials->password,
            'password' => $credentials->password_vend,
            'meter_number' => $meter->serial_number,
        ];
        return [
            'result_code' => $this->calinSmartMeterApiRequests->post($url, $tokenParams)
        ];
    }

}
