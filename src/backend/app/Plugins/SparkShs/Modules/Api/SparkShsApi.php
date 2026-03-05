<?php

namespace App\Plugins\SparkShs\Modules\Api;

use App\DTO\TransactionDataContainer;
use App\Events\NewLogEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Models\Device;
use App\Models\Token;
use App\Plugins\SparkShs\Http\Clients\SparkShsApiClient;
use App\Plugins\SparkShs\Models\SparkShsTransaction;
use Illuminate\Support\Facades\Log;

class SparkShsApi implements IManufacturerAPI {
    public function __construct(
        private SparkShsTransaction $sparkShsTransaction,
        private SparkShsApiClient $apiClient,
    ) {}

    /**
     * @return array{token: string, token_type: string, token_unit: string, token_amount: float}
     */
    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $dayDifferenceBetweenTwoInstallments = $transactionContainer->dayDifferenceBetweenTwoInstallments;
        $minimumPurchaseAmount = $transactionContainer->installmentCost;
        $minimumPurchaseAmountPerDay = ($minimumPurchaseAmount / $dayDifferenceBetweenTwoInstallments); // This is for 1 day of energy
        $transactionContainer->chargedEnergy = 0; // will represent the day count
        $transactionContainer->chargedEnergy += ceil($transactionContainer->rawAmount / $minimumPurchaseAmountPerDay);

        Log::debug('ENERGY TO BE CHARGED as Day '.$transactionContainer->chargedEnergy.
            ' Manufacturer => SparkShsApi');

        $chargeDays = $transactionContainer->chargedEnergy;
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
    public function unlockDevice(TransactionDataContainer $transactionContainer): array {
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
            'token_type' => Token::TYPE_UNLOCK,
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
}
