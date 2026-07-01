<?php

namespace App\Plugins\DemoShsManufacturer;

use App\DTO\TransactionDataContainer;
use App\Events\NewLogEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Lib\IManufacturerDeviceInfo;
use App\Models\Device;
use App\Models\Token;
use App\Plugins\DemoShsManufacturer\Models\DemoShsTransaction;

/**
 * Demo SHS Manufacturer API for demo purposes.
 * Returns random tokens for device charging operations without making real API calls.
 */
class DemoShsManufacturerApi implements IManufacturerAPI, IManufacturerDeviceInfo {
    public function __construct(
        private DemoShsTransaction $demoShsTransaction,
    ) {}

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $dayDifferenceBetweenTwoInstallments = $transactionContainer->dayDifferenceBetweenTwoInstallments;
        $minimumPurchaseAmount = $transactionContainer->installmentCost;
        $minimumPurchaseAmountPerDay = ($minimumPurchaseAmount / $dayDifferenceBetweenTwoInstallments); // This is for 1 day of energy
        $transactionContainer->chargeAmount = ceil($transactionContainer->amount / $minimumPurchaseAmountPerDay);
        $transactionContainer->chargeUnit = Token::UNIT_DAYS;
        $transactionContainer->chargeType = Token::TYPE_TIME;

        $energy = $transactionContainer->chargeAmount;

        $randomToken = $this->generateRandomToken();

        $this->recordTransaction($transactionContainer);
        $this->logAction($transactionContainer, "Demo Token: $randomToken created for $energy days usage.");

        return [
            'token' => $randomToken,
            'token_type' => Token::TYPE_TIME,
            'token_unit' => Token::UNIT_DAYS,
            'token_amount' => $energy,
        ];
    }

    public function unlockDevice(TransactionDataContainer $transactionContainer): array {
        $randomToken = $this->generateRandomToken();

        $this->recordTransaction($transactionContainer);
        $this->logAction($transactionContainer, "Demo Token: $randomToken created for unlocking device.");

        return [
            'token' => $randomToken,
            'token_type' => Token::TYPE_UNLOCK,
            'token_unit' => null,
            'token_amount' => null,
        ];
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
     * Simulates a manufacturer device lookup. A serial ending in 0 (or empty)
     * is reported as not mapped so both the mapped and not-mapped outcomes can
     * be demonstrated without a real manufacturer account.
     *
     * @return array{mapped: bool, device: array<string, mixed>|null}
     */
    public function getDeviceInfo(Device $device): array {
        $serial = (string) $device->device_serial;

        if ($serial === '' || str_ends_with($serial, '0')) {
            return ['mapped' => false, 'device' => null];
        }

        return [
            'mapped' => true,
            'device' => [
                'serial' => $serial,
                'model' => 'Demo SHS Unit',
                'status' => 'active',
                'firmware' => '1.0.0',
            ],
        ];
    }

    private function generateRandomToken(): string {
        return sprintf(
            '%04d-%04d-%04d-%04d',
            random_int(1000, 9999),
            random_int(1000, 9999),
            random_int(1000, 9999),
            random_int(1000, 9999)
        );
    }

    private function recordTransaction(TransactionDataContainer $transactionContainer): void {
        $manufacturerTransaction = $this->demoShsTransaction->newQuery()->create([]);

        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'demo_shs_transaction',
        ]);
    }

    private function logAction(TransactionDataContainer $transactionContainer, string $action): void {
        event(new NewLogEvent([
            'user_id' => -1,
            'affected' => $transactionContainer->appliancePerson,
            'action' => $action,
        ]));
    }
}
