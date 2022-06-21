<?php

namespace Inensus\KelinMeter;

use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\Transaction;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Inensus\KelinMeter\Exceptions\KelinApiResponseException;
use Inensus\KelinMeter\Http\Clients\KelinMeterApiClient;
use Inensus\KelinMeter\Models\KelinCustomer;
use Inensus\KelinMeter\Models\KelinMeter;
use Inensus\KelinMeter\Models\KelinTransaction;
use Inensus\KelinMeter\Services\KelinCredentialService;
use Inensus\KelinMeter\Services\KelinCustomerService;
use Inensus\KelinMeter\Services\KelinMeterService;


class KelinMeterApi implements IManufacturerAPI
{
    private $rootUrl = '/recharge';
    private $meterParameter;
    private $kelinCustomer;
    private $kelinMeter;
    private $kelinMeterService;
    private $credentialService;
    private $customerService;
    private $kelinTransaction;
    private $transaction;
    private $kelinApi;

    public function __construct(
        MeterParameter $meterParameter,
        KelinCustomer $kelinCustomer,
        KelinMeter $kelinMeter,
        KelinMeterService $kelinMeterService,
        KelinCredentialService $credentialService,
        KelinCustomerService $customerService,
        KelinTransaction $kelinTransaction,
        Transaction $transaction,
        KelinMeterApiClient $kelinApi
    ) {
        $this->meterParameter = $meterParameter;
        $this->kelinCustomer = $kelinCustomer;
        $this->credentialService = $credentialService;
        $this->customerService = $customerService;
        $this->kelinTransaction = $kelinTransaction;
        $this->kelinMeter = $kelinMeter;
        $this->kelinMeterService = $kelinMeterService;
        $this->transaction = $transaction;
        $this->kelinApi = $kelinApi;
    }

    public function chargeMeter($transactionContainer): array
    {
        $meterParameter = $transactionContainer->meterParameter;
        $tariff = $meterParameter->tariff()->first();
        $transactionContainer->chargedEnergy += $transactionContainer->amount / ($tariff->total_price / 100);
        Log::critical('ENERGY TO BE CHARGED float ' .
            (float)$transactionContainer->chargedEnergy .
            ' Manufacturer => Kelin');
          if (config('app.debug')) {
               return [
                   'token' => 'debug-token',
                   'energy' => (float)$transactionContainer->chargedEnergy,
               ];
           } else {
        $amount = $transactionContainer->totalAmount;
        try {
            $kelinMeter = $this->kelinMeter->newQuery()->where(
                'mpm_meter_id',
                $meterParameter->meter->id
            )->firstOrFail();
        } catch (ModelNotFoundException $e) {
            Log::critical('No Meter found for transaction data.', ['message' => $e->getMessage()]);
            throw new ModelNotFoundException($e->getMessage());
        }
        $queryParams = [
            'meterNo' => $kelinMeter->meter_address,
            'tariff' => $tariff->total_price / 100,
            'recharge' => $amount,
            'energy'=>$transactionContainer->chargedEnergy,
            'rechargeTime' => Carbon::now()->format('Y-m-d'),
        ];

        try {
            $result = $this->kelinApi->get($this->rootUrl, $queryParams);
        } catch (KelinApiResponseException $exception) {
            Log::critical(
                'Kelin API Transaction Failed',
                ['Body :' => json_encode($queryParams), 'message :' => $exception->getMessage()]
            );
            throw new KelinApiResponseException($exception->getMessage());
        } catch (GuzzleException $exception) {
            Log::critical(
                'Unknown exception while authenticating KelinMeter',
                ['reason' => $exception->getMessage()]
            );
            throw new KelinApiResponseException($exception->getMessage());
        }
        $transactionResult = [
            'opType' => $result['data']['opType'],
            'payKWH' => $result['data']['payKWH'],
            'openToken1' => $result['data']['openToken1'],
            'openToken2' => $result['data']['openToken2'],
            'payToken' => $result['data']['payToken'],
            'meterSerial' => $meterParameter->meter->serial_number,
            'amount' => $amount,
        ];
        $this->associateManufacturerTransaction($transactionContainer, $transactionResult);
        $token = $transactionResult['opType'] === 2 ? sprintf('EnergyToken : %s',
            $transactionResult['payToken']) : sprintf('OpenToken1 : %s OpenToken2 : %s', $transactionResult['openToken1'],
            $transactionResult['openToken2']);
            return [
                'token' => $token,
                'energy' => $transactionContainer->chargedEnergy
            ];
       }
    }

    public function clearMeter(Meter $meter)
    {
    }

    public function associateManufacturerTransaction(
        TransactionDataContainer $transactionContainer,
        $transactionResult = []
    ) {
        $manufacturerTransaction = $this->kelinTransaction->newQuery()->create([
            'meter_serial' => $transactionResult['meterSerial'],
            'amount' => $transactionResult['amount'],
            'op_type' => $transactionResult['opType'],
            'pay_kwh' => $transactionResult['payKWH'],
            'open_token_1' => $transactionResult['openToken1'],
            'open_token_2' => $transactionResult['openToken2'],
            'pay_token' => $transactionResult['payToken'],
        ]);
        $transactionContainer->transaction->originalTransaction()->associate($manufacturerTransaction)->save();
    }
}
