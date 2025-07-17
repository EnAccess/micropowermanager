<?php

namespace Inensus\DalyBms\Modules\Api;

use App\Events\NewLogEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\Token;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Inensus\DalyBms\Exceptions\DalyBmsApiResponseException;
use Inensus\DalyBms\Models\DalyBmsTransaction;
use Inensus\DalyBms\Services\DalyBmsCredentialService;
use MPM\EBike\EBikeService;

class DalyBmsApi implements IManufacturerAPI {
    // works with query params
    public const COMMAND_DEVICES_LIST = '/Monitor/Refresh';
    // works with query params
    public const COMMAND_DEVICE_DETAIL = '/Monitor/ShowMonitorTips?';
    // works with body params
    public const COMMAND_SWITCH = '/Monitor/Send';

    public function __construct(
        private DalyBmsCredentialService $credentialService,
        private DalyBmsTransaction $dalyBmsTransaction,
        private EBikeService $eBikeService,
        private ApiRequests $apiRequests,
    ) {}

    public function getDevices(array $deviceSerials) {
        $params = [
            'codes' => $deviceSerials,
        ];

        $credentials = $this->credentialService->getCredentials();

        try {
            if (!$this->credentialService->isAccessTokenValid($credentials)) {
                $authResponse = $this->apiRequests->authentication($credentials);
                $this->credentialService->updateCredentials($credentials, $authResponse);
            }

            return $this->apiRequests->postWithBodyParams($credentials, $params, self::COMMAND_DEVICES_LIST);
        } catch (DalyBmsApiResponseException $e) {
            $this->credentialService->updateCredentials(
                $credentials,
                ['access_token' => null, 'token_expires_in' => null]
            );
            throw $e;
        }
    }

    public function getDevice(string $code) {
        $params = [
            'Code' => $code,
        ];

        $credentials = $this->credentialService->getCredentials();
        try {
            if (!$this->credentialService->isAccessTokenValid($credentials)) {
                $authResponse = $this->apiRequests->authentication($credentials);
                $this->credentialService->updateCredentials($credentials, $authResponse);
            }

            return $this->apiRequests->postWithQueryParams($credentials, $params, self::COMMAND_DEVICE_DETAIL);
        } catch (DalyBmsApiResponseException $e) {
            $this->credentialService->updateCredentials(
                $credentials,
                ['access_token' => null, 'token_expires_in' => null]
            );
            throw $e;
        }
    }

    public function switchDevice(string $code, bool $isOn) {
        $params = [
            'cmdKey' => '8500_004',
            'data' => [
                'CmdKey' => '8500_004',
                'Code' => $code,
                'ACC' => $isOn ? 1 : 0,
            ],
        ];

        $credentials = $this->credentialService->getCredentials();
        try {
            if (!$this->credentialService->isAccessTokenValid($credentials)) {
                $authResponse = $this->apiRequests->authentication($credentials);
                $this->credentialService->updateCredentials($credentials, $authResponse);
            }

            return $this->apiRequests->postWithBodyParams($credentials, $params, self::COMMAND_SWITCH);
        } catch (DalyBmsApiResponseException $e) {
            throw $e;
        }
    }

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $transactionId = $transactionContainer->transaction->id;
        $dayDifferenceBetweenTwoInstallments = $transactionContainer->dayDifferenceBetweenTwoInstallments;
        $minimumPurchaseAmount = $transactionContainer->installmentCost;
        $minimumPurchaseAmountPerDay = ($minimumPurchaseAmount / $dayDifferenceBetweenTwoInstallments); // This is for 1 day of energy
        $transactionContainer->chargedEnergy = 0; // will represent the day count
        $transactionContainer->chargedEnergy += ceil($transactionContainer->rawAmount / $minimumPurchaseAmountPerDay);

        Log::debug('ENERGY TO BE CHARGED as Day '.$transactionContainer->chargedEnergy.
            ' Manufacturer => DalyBmsApi');

        $device = $transactionContainer->device;
        $energy = $transactionContainer->chargedEnergy;
        $deviceSerial = $device->device_serial;
        $this->switchDevice($deviceSerial, true);
        $manufacturerTransaction = $this->dalyBmsTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'daly_bms_transaction',
        ]);
        $eBike = $this->eBikeService->getBySerialNumber($deviceSerial);
        $status = $eBike->status;
        $updatingData = [
            'status' => str_replace('ACCOFF', 'ACCON', $status),
        ];
        $this->eBikeService->update(
            $eBike,
            $updatingData
        );
        $creator = User::query()->firstOrCreate([
            'name' => 'System',
        ]);
        event(new NewLogEvent([
            'user_id' => $creator->id,
            'affected' => $eBike,
            'action' => "Bike ($deviceSerial) is unlocked with transaction id: $transactionId",
        ]));

        return [
            'token' => '-',
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_DAYS,
            'token_amount' => $energy,
        ];
    }

    public function clearDevice(Device $device): void {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }
}
