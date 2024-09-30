<?php

namespace Inensus\SunKingSHS\Modules\Api;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use Illuminate\Support\Facades\Log;
use Inensus\SunKingSHS\Exceptions\SunKingApiResponseException;
use Inensus\SunKingSHS\Models\SunKingTransaction;
use Inensus\SunKingSHS\Services\SunKingCredentialService;

class SunKingSHSApi implements IManufacturerAPI
{
    public const API_CALL_TOKEN_GENERATION = '/token';
    public const COMMAND_ADD_CREDIT = 'add_credit';

    public function __construct(
        private SunKingCredentialService $credentialService,
        private SunKingTransaction $sunKingTransaction,
        private ApiRequests $apiRequests,
    ) {
    }

    public function chargeDevice(TransactionDataContainer $transactionContainer): array
    {
        $dayDifferenceBetweenTwoInstallments = $transactionContainer->dayDifferenceBetweenTwoInstallments;
        $minimumPurchaseAmount = $transactionContainer->installmentCost;
        $minimumPurchaseAmountPerDay = ($minimumPurchaseAmount / $dayDifferenceBetweenTwoInstallments); // This is for 1 day of energy
        $transactionContainer->chargedEnergy = 0; // will represent the day count
        $transactionContainer->chargedEnergy += ceil($transactionContainer->rawAmount / $minimumPurchaseAmountPerDay);

        Log::debug('ENERGY TO BE CHARGED as Day '.$transactionContainer->chargedEnergy.
            ' Manufacturer => SunKingSHSApi');

        $device = $transactionContainer->device;
        $energy = $transactionContainer->chargedEnergy;

        $params = [
            'device' => $device->device_serial,
            'command' => self::COMMAND_ADD_CREDIT,
            'payload' => $energy,
            'time_unit' => 'day',
        ];
        $credentials = $this->credentialService->getCredentials();

        try {
            $authResponse = $this->apiRequests->authentication($credentials);
            $this->credentialService->updateCredentials($credentials, $authResponse);
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
        event(
            'new.log',
            [
                'logData' => [
                    'user_id' => -1,
                    'affected' => $transactionContainer->appliancePerson,
                    'action' => 'Token: '.$response['token'].' created for '.$energy.
                        ' days usage.',
                ],
            ]
        );

        return [
            'token' => $response['token'],
            'load' => $energy,
        ];
    }

    public function clearDevice(Device $device)
    {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }
}
