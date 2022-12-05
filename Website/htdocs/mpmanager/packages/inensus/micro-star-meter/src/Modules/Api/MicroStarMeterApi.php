<?php

namespace Inensus\MicroStarMeter\Modules\Api;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\Transaction;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Inensus\MicroStarMeter\Models\MicroStarTransaction;
use Inensus\MicroStarMeter\Services\MicroStarCredentialService;


class MicroStarMeterApi implements IManufacturerAPI
{
    const API_CALL_CHARGE_METER = '/getStsVendingToken?';

    public function __construct(
        private MicroStarCredentialService $credentialService,
        private MicroStarTransaction $microStarTransaction,
        private ApiRequests $apiRequests
    ) {

    }

    public function chargeMeter(TransactionDataContainer $transactionContainer): array
    {
        $meterParameter = $transactionContainer->meterParameter;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / ($meterParameter->tariff->total_price);

        Log::debug('ENERGY TO BE CHARGED float ' . (float)$transactionContainer->chargedEnergy .
            ' Manufacturer => MicroStarMeterApi');


        $meter = $transactionContainer->meter;
        $energy = (float)$transactionContainer->chargedEnergy;
        $params = ['deviceNo' => $meter->serial_number, 'rechargeAmount' => $energy]; // if they accepts
        // rechargeAmount as money, then we have to convert it to money
        $credentials = $this->credentialService->getCredentials();
        if (config('app.env') === 'local' || config('app.env') === 'development') {
            //debug token for development
            $response['token'] = '48725997619297311927';
        } else {
            $response = $this->apiRequests->get($credentials, $params, self::API_CALL_CHARGE_METER);
        }

        $this->associateManufacturerTransaction($transactionContainer);
        return [
            'token' => $response['token'],
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
        $manufacturerTransaction = $this->microStarTransaction->newQuery()->create();
        $transactionContainer->transaction->originalTransaction()->associate($manufacturerTransaction)->save();
    }
}
