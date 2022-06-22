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
        $transactionContainer->chargedEnergy += $transactionContainer->amount
            / ($meterParameter->tariff()->first()->total_price / 100);
        Log::critical('ENERGY TO BE CHARGED float ' . (float)$transactionContainer->chargedEnergy .
            ' Manufacturer => Calin Smart');
        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => (float)$transactionContainer->chargedEnergy,
            ];
        } else {
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
                'password' =>  $credentials->password,
                'password_vend' => $credentials->password_vend,
                'meter_number' => $meter->serial_number,
                'is_vend_by_unit' => true,
                'amount' => $energy
            ];
            $token = $this->calinSmartMeterApiRequests->post($url, $tokenParams);
            $this->associateManufacturerTransaction($transactionContainer);
            return [
                'token' => $token,
                'energy' => $energy
            ];
        }
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
    /**
     * @param TransactionDataContainer $transactionContainer
     * @return void
     */
    public function associateManufacturerTransaction(TransactionDataContainer $transactionContainer): void
    {
        $manufacturerTransaction = $this->calinSmartTransaction->newQuery()->create([
            'transaction_id' => $transactionContainer->transaction->id,
        ]);
        $transactionContainer->transaction->originalTransaction()->associate($manufacturerTransaction)->save();
    }
}
