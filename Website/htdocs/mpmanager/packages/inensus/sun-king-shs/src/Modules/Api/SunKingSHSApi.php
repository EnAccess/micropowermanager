<?php

namespace Inensus\SunKingSHS\Modules\Api;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Meter\Meter;
use Illuminate\Support\Facades\Log;
use Inensus\SunKingSHS\Exceptions\SunKingApiResponseException;
use Inensus\SunKingSHS\Models\SunKingTransaction;
use Inensus\SunKingSHS\Services\SunKingCredentialService;


class SunKingSHSApi implements IManufacturerAPI
{
    const API_CALL_TOKEN_GENERATION = '/token';
    const COMMAND_ADD_CREDIT = 'add_credit';

    public function __construct(
        private SunKingCredentialService $credentialService,
        private SunKingTransaction $sunKingTransaction,
        private ApiRequests $apiRequests
    ) {

    }

    public function chargeMeter(TransactionDataContainer $transactionContainer): array
    {
        $meterParameter = $transactionContainer->meterParameter;
        $minimumPurchaseAmount = $transactionContainer->tariff->minimum_purchase_amount; //This is for 7 days of energy
        $minimumPurchaseAmountPerDay = ($minimumPurchaseAmount / 7); //This is for 1 day of energy
        $transactionContainer->chargedEnergy = 0; // will represent the day count
        $transactionContainer->chargedEnergy += ceil($transactionContainer->rawAmount / ($minimumPurchaseAmountPerDay));

        Log::debug('ENERGY TO BE CHARGED as Day ' . $transactionContainer->chargedEnergy .
            ' Manufacturer => SunKingSHSApi');

        $meter = $transactionContainer->meter;
        $energy = $transactionContainer->chargedEnergy;

        $params = [
            "device" => $meter->serial_number,
            "command" => self::COMMAND_ADD_CREDIT,
            "payload" => $energy,
            "time_unit" => "day"
        ];

        $credentials = $this->credentialService->getCredentials();

        if (!$this->credentialService->isAccessTokenValid($credentials)) {
            $authResponse = $this->apiRequests->authentication($credentials);

            $this->credentialService->updateCredentials($credentials, $authResponse);
        }

        try {
            $response = $this->apiRequests->post($credentials, $params, self::API_CALL_TOKEN_GENERATION);
        } catch (SunKingApiResponseException $e) {
            $this->credentialService->updateCredentials($credentials,
                ['access_token' => null, 'token_expires_in' => null]);
            throw new SunKingApiResponseException($e->getMessage());
        }


        $manufacturerTransaction = $this->sunKingTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'sun_king_transaction',
        ]);

        if ($transactionContainer->shsLoan) {
            event(
                'new.log',
                [
                    'logData' => [
                        'user_id' => -1,
                        'affected' => $transactionContainer->shsLoan,
                        'action' => 'Token: ' . $response['token'] . ' created for ' . $energy .
                            ' days usage.'
                    ]
                ]
            );
        }


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


}
