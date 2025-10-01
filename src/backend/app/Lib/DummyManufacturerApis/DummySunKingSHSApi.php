<?php

namespace App\Lib\DummyManufacturerApis;

use App\Events\NewLogEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\Token;
use Inensus\SunKingSHS\Models\SunKingTransaction;

/**
 * Dummy SunKing SHS API for demo purposes.
 * Returns random tokens for device charging operations.
 */
class DummySunKingSHSApi implements IManufacturerAPI {
    public function __construct(
        private SunKingTransaction $sunKingTransaction,
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
            rand(1000, 9999),
            rand(1000, 9999),
            rand(1000, 9999),
            rand(1000, 9999)
        );
    }

    private function recordTransaction(TransactionDataContainer $transactionContainer): void {
        $manufacturerTransaction = $this->sunKingTransaction->newQuery()->create([]);

        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'sun_king_transaction',
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
}
