<?php

namespace App\Plugins\SparkShs\Modules\Api;

use App\DTO\TransactionDataContainer;
use App\Enums\VendingCapability;
use App\Events\NewLogEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerDeviceControl;
use App\Lib\ITokenIssuer;
use App\Models\AppliancePerson;
use App\Models\Device;
use App\Models\Token;
use App\Plugins\SparkShs\Exceptions\SparkShsApiResponseException;
use App\Plugins\SparkShs\Http\Clients\SparkShsApiClient;
use App\Plugins\SparkShs\Models\SparkShsTransaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SparkShsApi implements ITokenIssuer, IManufacturerDeviceControl {
    public function __construct(
        private SparkShsTransaction $sparkShsTransaction,
        private SparkShsApiClient $apiClient,
    ) {}

    /**
     * @return list<VendingCapability>
     */
    public function capabilities(): array {
        return [
            VendingCapability::AddCredit,
            VendingCapability::UnlockPaygo,
            VendingCapability::ResetCredit,
        ];
    }

    /**
     * @return array{token: string, token_type: string, token_unit: string, token_amount: float}
     */
    public function addCredit(TransactionDataContainer $transactionContainer): array {
        $transactionContainer->chargeAmount = $transactionContainer->creditDays();
        $transactionContainer->chargeUnit = Token::UNIT_DAYS;
        $transactionContainer->chargeType = Token::TYPE_TIME;

        Log::debug('CHARGE AMOUNT as Day '.$transactionContainer->chargeAmount.
            ' Manufacturer => SparkShsApi');

        $chargeDays = $transactionContainer->chargeAmount;
        $deviceSerial = $transactionContainer->device->device_serial;

        $payload = [
            'type' => 'days',
            'days' => $chargeDays,
        ];

        $response = $this->apiClient->post(
            "products/kits/{$deviceSerial}/tokens",
            $payload
        );

        $this->recordTransaction($transactionContainer);
        $this->logAction($transactionContainer, "Token: {$response['token']} created for $chargeDays days usage.");

        return [
            'token' => $response['token'],
            'token_type' => Token::TYPE_TIME,
            'token_unit' => Token::UNIT_DAYS,
            'token_amount' => $chargeDays,
        ];
    }

    /**
     * @return array{token: string, token_type: string, token_unit: null, token_amount: null}
     */
    public function unlockPaygo(TransactionDataContainer $transactionContainer): array {
        $deviceSerial = $transactionContainer->device->device_serial;

        $payload = [
            'type' => 'unlock',
        ];

        $response = $this->apiClient->post(
            "products/kits/{$deviceSerial}/tokens",
            $payload
        );

        $this->recordTransaction($transactionContainer);
        $this->logAction($transactionContainer, "Token: {$response['token']} created for unlocking device.");

        return [
            'token' => $response['token'],
            'token_type' => Token::TYPE_UNLOCK_PAYGO,
            'token_unit' => null,
            'token_amount' => null,
        ];
    }

    private function recordTransaction(TransactionDataContainer $transactionContainer): void {
        $manufacturerTransaction = $this->sparkShsTransaction->newQuery()->create([]);

        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'spark_shs_transaction',
        ]);
    }

    private function logAction(TransactionDataContainer $transactionContainer, string $action): void {
        // Tokens for unsold devices come from device control, which logs them against the device.
        if (!$transactionContainer->appliancePerson instanceof AppliancePerson) {
            return;
        }

        event(new NewLogEvent([
            'user_id' => -1,
            'affected' => $transactionContainer->appliancePerson,
            'action' => $action,
        ]));
    }

    /**
     * Spark's reset token sets the kit's credit to 0.
     *
     * @return array{token: string, token_type: string, token_unit: null, token_amount: null}
     */
    public function resetCredit(Device $device): array {
        $response = $this->apiClient->post(
            "products/kits/{$device->device_serial}/tokens",
            ['type' => 'reset']
        );

        // Recording an empty token would leave the unit running on the credit it was
        // meant to lose, so the status is checked before the token is handed back.
        if (!$response->successful()) {
            throw new SparkShsApiResponseException("Spark SHS reset token request failed with status {$response->status()}.");
        }

        return [
            'token' => $response['token'],
            'token_type' => Token::TYPE_RESET_CREDIT,
            'token_unit' => null,
            'token_amount' => null,
        ];
    }

    public function clearCredit(Device $device): array {
        throw new ApiCallDoesNotSupportedException('Spark SHS does not support clearing meter credit.');
    }

    /**
     * Looks up the kit on Spark to check whether the serial stored on MPM is
     * still mapped on the manufacturer side. A forbidden response status (403) is reported as
     * not mapped rather than as a failure.
     *
     * @return array{mapped: bool, device: array<string, mixed>|null}
     */
    public function getDeviceInfo(Device $device): array {
        $response = $this->apiClient->get("products/kits/{$device->device_serial}");

        if ($response->status() === 403) {
            return ['mapped' => false, 'device' => null];
        }

        if (!$response->successful()) {
            throw new SparkShsApiResponseException("Spark SHS device lookup failed with status {$response->status()}.");
        }

        return ['mapped' => true, 'device' => Arr::only((array) $response->json(), ['serial', 'type'])];
    }
}
