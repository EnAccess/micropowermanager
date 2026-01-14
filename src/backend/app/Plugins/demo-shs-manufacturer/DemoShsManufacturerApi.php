<?php

namespace Inensus\DemoShsManufacturer;

use App\DTO\TransactionDataContainer;
use App\Events\NewLogEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Models\Device;
use App\Models\Token;
use Inensus\DemoShsManufacturer\Models\DemoShsTransaction;

/**
 * Demo SHS Manufacturer API for demo purposes.
 * Returns random tokens for device charging operations without making real API calls.
 */
class DemoShsManufacturerApi implements IManufacturerAPI {
    public function __construct(
        private DemoShsTransaction $demoShsTransaction,
    ) {}

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $dayDifferenceBetweenTwoInstallments = $transactionContainer->dayDifferenceBetweenTwoInstallments;
        $minimumPurchaseAmount = $transactionContainer->installmentCost;
        $minimumPurchaseAmountPerDay = ($minimumPurchaseAmount / $dayDifferenceBetweenTwoInstallments); // This is for 1 day of energy
        $transactionContainer->chargedEnergy = 0; // will represent the day count
        $transactionContainer->chargedEnergy += ceil($transactionContainer->rawAmount / $minimumPurchaseAmountPerDay);

        $energy = $transactionContainer->chargedEnergy;

        // Generate a random token for demo purposes
        $randomToken = $this->generateRandomToken();

        // Record transaction like the real API
        $this->recordTransaction($transactionContainer);
        $this->logAction($transactionContainer, $randomToken);

        return [
            'token' => $randomToken,
            'token_type' => Token::TYPE_TIME,
            'token_unit' => Token::UNIT_DAYS,
            'token_amount' => $energy,
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
     * Generate a random token for demo purposes.
     */
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

    private function logAction(TransactionDataContainer $transactionContainer, string $token): void {
        $isInstallmentsCompleted = $this->isInstallmentsCompleted($transactionContainer);
        $energy = $transactionContainer->chargedEnergy;
        $action = $isInstallmentsCompleted
            ? "Demo Token: $token created for unlocking device."
            : "Demo Token: $token created for $energy days usage.";

        event(new NewLogEvent([
            'user_id' => -1,
            'affected' => $transactionContainer->appliancePerson,
            'action' => $action,
        ]));
    }

    private function isInstallmentsCompleted(TransactionDataContainer $transactionContainer): bool {
        return $transactionContainer->applianceInstallmentsFullFilled;
    }
}
