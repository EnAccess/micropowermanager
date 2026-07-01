<?php

namespace App\Plugins\SunKingSHS\Modules\Api;

use App\DTO\TransactionDataContainer;
use App\Events\NewLogEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Lib\IManufacturerDeviceInfo;
use App\Models\Device;
use App\Models\Token;
use App\Plugins\SunKingSHS\Exceptions\SunKingApiResponseException;
use App\Plugins\SunKingSHS\Http\Clients\SunKingSHSApiClient;
use App\Plugins\SunKingSHS\Models\SunKingCredential;
use App\Plugins\SunKingSHS\Models\SunKingTransaction;
use App\Plugins\SunKingSHS\Services\SunKingCredentialService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SunKingSHSApi implements IManufacturerAPI, IManufacturerDeviceInfo {
    public const API_CALL_TOKEN_GENERATION = '/token';
    public const API_CALL_DEVICE_DETAILS = '/device_details/';
    public const COMMAND_ADD_CREDIT = 'add_credit';
    public const COMMAND_UNLOCK_DEVICE = 'unlock';

    public function __construct(
        private SunKingCredentialService $credentialService,
        private SunKingTransaction $sunKingTransaction,
        private SunKingSHSApiClient $apiClient,
    ) {}

    /**
     * @return array{token: string, token_type: string, token_unit: string, token_amount: float}
     */
    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $dayDifferenceBetweenTwoInstallments = $transactionContainer->dayDifferenceBetweenTwoInstallments;
        $minimumPurchaseAmount = $transactionContainer->installmentCost;
        $minimumPurchaseAmountPerDay = ($minimumPurchaseAmount / $dayDifferenceBetweenTwoInstallments); // This is for 1 day of energy
        $transactionContainer->chargeAmount = ceil($transactionContainer->amount / $minimumPurchaseAmountPerDay);
        $transactionContainer->chargeUnit = Token::UNIT_DAYS;
        $transactionContainer->chargeType = Token::TYPE_TIME;

        Log::debug('CHARGE AMOUNT as Day '.$transactionContainer->chargeAmount.
            ' Manufacturer => SunKingSHSApi');

        $energy = $transactionContainer->chargeAmount;
        $params = $this->buildParams($transactionContainer);
        $credentials = $this->credentialService->getCredentials();
        $response = $this->handleApiRequest($credentials, $params);

        $this->recordTransaction($transactionContainer);
        $this->logAction($transactionContainer, "Token: {$response['token']} created for $energy days usage.");

        return [
            'token' => $response['token'],
            'token_type' => Token::TYPE_TIME,
            'token_unit' => Token::UNIT_DAYS,
            'token_amount' => $energy,
        ];
    }

    /**
     * @return array{token: string, token_type: string, token_unit: null, token_amount: null}
     */
    public function unlockDevice(TransactionDataContainer $transactionContainer): array {
        $params = [
            'device' => $transactionContainer->device->device_serial,
            'command' => self::COMMAND_UNLOCK_DEVICE,
        ];
        $credentials = $this->credentialService->getCredentials();
        $response = $this->handleApiRequest($credentials, $params);

        $this->recordTransaction($transactionContainer);
        $this->logAction($transactionContainer, "Token: {$response['token']} created for unlocking device.");

        return [
            'token' => $response['token'],
            'token_type' => Token::TYPE_UNLOCK,
            'token_unit' => null,
            'token_amount' => null,
        ];
    }

    /**
     * @return array{device: string, command: string, payload: float, time_unit: string}
     */
    private function buildParams(TransactionDataContainer $transactionContainer): array {
        return [
            'device' => $transactionContainer->device->device_serial,
            'command' => self::COMMAND_ADD_CREDIT,
            'payload' => $transactionContainer->chargeAmount,
            'time_unit' => 'day',
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    private function handleApiRequest(SunKingCredential &$credentials, array $params): mixed {
        try {
            $authResponse = $this->apiClient->authentication($credentials);
            $this->credentialService->updateCredentials($credentials, $authResponse);

            return $this->apiClient->post($credentials, $params, self::API_CALL_TOKEN_GENERATION);
        } catch (SunKingApiResponseException $e) {
            $this->credentialService->updateCredentials($credentials, [
                'access_token' => null,
                'token_expires_in' => null,
            ]);

            throw new SunKingApiResponseException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function recordTransaction(TransactionDataContainer $transactionContainer): void {
        $manufacturerTransaction = $this->sunKingTransaction->newQuery()->create([]);

        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'sun_king_transaction',
        ]);
    }

    private function logAction(TransactionDataContainer $transactionContainer, string $action): void {
        event(new NewLogEvent([
            'user_id' => -1,
            'affected' => $transactionContainer->appliancePerson,
            'action' => $action,
        ]));
    }

    /**
     * @return array<string,mixed>|null
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearDevice(Device $device): ?array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }

    /**
     * Queries SunKing for the device unit so callers can tell whether the
     * serial stored on MPM is still mapped on the manufacturer side. A missing
     * device (404) is reported as not mapped rather than as a failure.
     *
     * @return array{mapped: bool, device: array<string, mixed>|null}
     */
    public function getDeviceInfo(Device $device): array {
        $credentials = $this->credentialService->getCredentials();
        $authResponse = $this->apiClient->authentication($credentials);
        $this->credentialService->updateCredentials($credentials, $authResponse);

        $response = $this->apiClient->get($credentials, self::API_CALL_DEVICE_DETAILS.$device->device_serial);

        if ($response === null) {
            return ['mapped' => false, 'device' => null];
        }

        $deviceData = $response['device'] ?? $response;

        return [
            'mapped' => true,
            'device' => Arr::only($deviceData, ['code', 'name', 'is_paygo', 'is_gsm', 'version']),
        ];
    }
}
