<?php

namespace Inensus\AngazaSHS\Modules\Api;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Meter\Meter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Inensus\AngazaSHS\Exceptions\AngazaApiResponseException;
use Inensus\AngazaSHS\Models\AngazaTransaction;
use Inensus\AngazaSHS\Services\AngazaCredentialService;

class AngazaSHSApi implements IManufacturerAPI
{
    const API_CALL_UNIT_CREDIT = '/unit_credit';

    public function __construct(
        private AngazaCredentialService $credentialService,
        private AngazaTransaction $angazaTransaction,
        private ApiRequests $apiRequests
    ) {
    }

    public function chargeMeter(TransactionDataContainer $transactionContainer): array
    {
        $dayDifferenceBetweenTwoInstallments = $transactionContainer->dayDifferenceBetweenTwoInstallments;
        $minimumPurchaseAmount = $transactionContainer->installmentCost;
        $minimumPurchaseAmountPerDay =
            ($minimumPurchaseAmount / $dayDifferenceBetweenTwoInstallments); //This is for 1 day of energy
        $transactionContainer->chargedEnergy = 0; // will represent the day count
        $transactionContainer->chargedEnergy += ceil($transactionContainer->rawAmount / ($minimumPurchaseAmountPerDay));

        Log::debug('ENERGY TO BE CHARGED as Day ' . $transactionContainer->chargedEnergy .
            ' Manufacturer => AngazaSHSApi');

        $device = $transactionContainer->device;
        $energy = $transactionContainer->chargedEnergy;

        $params = [
            "unit_number" => $device->device_serial,
            "state" => [
                "desired" => [
                    "credit_until_dt" => Carbon::now()->addDays($energy)->toIso8601String()
                ]
            ]
        ];
        $credentials = $this->credentialService->getCredentials();

        try {
            $response = $this->apiRequests->put($credentials, $params, self::API_CALL_UNIT_CREDIT);

            if(isset($response['context'])){
                throw new AngazaApiResponseException($response['context']['reason']);
            }

        } catch (AngazaApiResponseException $e) {
            throw $e;
        }

        $manufacturerTransaction = $this->angazaTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'angaza_transaction',
        ]);

        $token = $response['_embedded']['latest_keycode']['keycode'];
        event(
            'new.log',
            [
                'logData' => [
                    'user_id' => -1,
                    'affected' => $transactionContainer->appliancePerson,
                    'action' => 'Token: ' . $token . ' created for ' . $energy .
                        ' days usage.'
                ]
            ]
        );
        return [
            'token' => $token,
            'load' => $energy
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

}
