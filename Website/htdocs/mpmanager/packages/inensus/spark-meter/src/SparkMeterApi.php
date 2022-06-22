<?php

namespace Inensus\SparkMeter;

use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\Transaction;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;
use Inensus\SparkMeter\Http\Requests\SparkMeterApiRequests;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Models\SmTariff;
use Inensus\SparkMeter\Models\SmTransaction;
use Inensus\SparkMeter\Services\TariffService;

class SparkMeterApi implements IManufacturerAPI
{
    /**
     * @var Client
     */
    protected $api;
    private $sparkMeterApiRequests;
    private $meterParameter;
    private $smCustomer;
    private $smTransaction;
    private $smTariff;
    private $tariffService;
    private $transaction;
    private $rootUrl = '/transaction/';

    public function __construct(
        SparkMeterApiRequests $sparkMeterApiRequests,
        Client $httpClient,
        TariffService $tariffService,
        MeterParameter $meterParameter,
        SmCustomer $smCustomer,
        SmTransaction $smTransaction,
        SmTariff $smTariff,
        Transaction $transaction
    ) {
        $this->sparkMeterApiRequests = $sparkMeterApiRequests;
        $this->api = $httpClient;
        $this->smCustomer = $smCustomer;
        $this->smTransaction = $smTransaction;
        $this->meterParameter = $meterParameter;
        $this->smTariff = $smTariff;
        $this->tariffService = $tariffService;
        $this->transaction = $transaction;
    }

    public function chargeMeter($transactionContainer): array
    {

        $meterParameter = $transactionContainer->meterParameter;

        $smTariff = $this->smTariff->newQuery()->where(
            'mpm_tariff_id',
            $meterParameter->tariff()->first()->id
        )->first();
        $tariff = $this->tariffService->singleSync($smTariff);
        $transactionContainer->chargedEnergy += $transactionContainer->amount / ($tariff->total_price / 100);
        Log::critical('ENERGY TO BE CHARGED float ' .
            (float)$transactionContainer->chargedEnergy .
            ' Manufacturer => Spark');

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => (float)$transactionContainer->chargedEnergy,
            ];
        } else {
            $amount = $transactionContainer->totalAmount;
            $externalId = $transactionContainer->transaction->id;

            try {
                $smCustomer = $this->smCustomer->newQuery()->with('site')->where(
                    'mpm_customer_id',
                    $meterParameter->owner->id
                )->firstOrFail();
            } catch (ModelNotFoundException $e) {
                Log::critical('No Customer found for transaction data.', ['message' => $e->getMessage()]);
                throw new ModelNotFoundException($e->getMessage());
            }

            $postParams = [
                'customer_id' => $smCustomer->customer_id,
                'amount' => strval($amount),
                'source' => 'cash',
                'external_id' => strval($externalId),

            ];

            try {
                $request = $this->api->post(
                    $smCustomer->site->thundercloud_url . '/transaction/',
                    [
                        'body' => json_encode($postParams),
                        'headers' => [
                            'Content-Type' => 'application/json;charset=utf-8',
                            'Authentication-Token' => $smCustomer->site->thundercloud_token
                        ],
                    ]
                );
                $result = json_decode((string)$request->getBody(), true);
            } catch (SparkAPIResponseException $e) {
                Log::critical(
                    'Spark API Transaction Failed',
                    ['Body :' => json_encode($postParams), 'message :' => $e->getMessage()]
                );
            }
            if ($result['error'] !== false && $result['error'] !== null) {
                throw new SparkAPIResponseException($result['error']);
            } else {
                $transactionInformation = $this->sparkMeterApiRequests->getInfo(
                    $this->rootUrl,
                    $result['transaction_id'],
                    $smCustomer->site->site_id
                );

                $transactionResult = [
                    'transaction_id' => $result['transaction_id'],
                    'site_id' => $smCustomer->site->site_id,
                    'customer_id' => $smCustomer->customer_id,
                    'status' => $transactionInformation['status'],
                    'external_id' => intval($transactionInformation['external_id']),
                ];

                $this->associateManufacturerTransaction($transactionContainer, $transactionResult);
            }
            $token = $smCustomer->site->site_id . '-' .
                $transactionInformation['source'] . '-' .
                $smCustomer->customer_id;
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
        $manufacturerTransaction = $this->smTransaction->newQuery()->create([
            'transaction_id' => $transactionResult['transaction_id'],
            'site_id' => $transactionResult['site_id'],
            'customer_id' => $transactionResult['customer_id'],
            'status' => $transactionResult['status'],
            'external_id' => $transactionResult['external_id']
        ]);
        $transactionContainer->transaction->originalTransaction()->associate($manufacturerTransaction)->save();
    }
}
