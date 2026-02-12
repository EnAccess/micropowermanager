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
use App\Plugins\SparkShs\Services\SparkShsCredentialService;
// use App\Plugins\SunKingSHS\Exceptions\SunKingApiResponseException;
// use App\Plugins\SunKingSHS\Models\SunKingCredential;
// use App\Plugins\SunKingSHS\Models\SunKingTransaction;
// use App\Plugins\SunKingSHS\Services\SunKingCredentialService;
use Illuminate\Support\Facades\Log;

class SparkShsApi implements IManufacturerAPI {
    public function __construct(
        private SparkShsCredentialService $credentialService,
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

        if ($transactionContainer->applianceInstallmentsFullFilled) {
            $payload = [
                'type' => 'unlock',
            ];
            $tokenType = Token::TYPE_UNLOCK;
            $tokenUnit = null;
            // FIXME: https://github.com/EnAccess/micropowermanager/issues/1304
            $tokenAmount = 1;
        } else {
            $payload = [
                'type' => 'days',
                'days' => $chargeDays,
            ];
            $tokenType = Token::TYPE_TIME;
            $tokenUnit = Token::UNIT_DAYS;
            $tokenAmount = $chargeDays;
        }

        $response = $this->apiClient->post(
            "products/kits/{$deviceSerial}/tokens",
            $payload
        );

        $this->recordTransaction($transactionContainer);
        $this->logAction($transactionContainer, $response['token']);

        return [
            'token' => $response['token'],
            'token_type' => $tokenType,
            'token_unit' => $tokenUnit,
            'token_amount' => $tokenAmount,
        ];
    }

    private function recordTransaction(TransactionDataContainer $transactionContainer): void {
        $manufacturerTransaction = $this->sparkShsTransaction->newQuery()->create([]);

        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'spark_shs_transaction',
        ]);
    }

    private function logAction(TransactionDataContainer $transactionContainer, string $token): void {
        $isInstallmentsCompleted = $this->isInstallmentsCompleted($transactionContainer);
        $energy = $transactionContainer->chargedEnergy;
        $action = $isInstallmentsCompleted
            ? "Token: $token created for unlocking device."
            : "Token: $token created for $energy days usage.";

        event(new NewLogEvent([
            'user_id' => -1,
            'affected' => $transactionContainer->appliancePerson,
            'action' => $action,
        ]));
    }

    private function isInstallmentsCompleted(TransactionDataContainer $transactionContainer): bool {
        return $transactionContainer->applianceInstallmentsFullFilled;
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
