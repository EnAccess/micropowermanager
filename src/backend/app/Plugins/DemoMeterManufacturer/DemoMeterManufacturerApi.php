<?php

namespace App\Plugins\DemoMeterManufacturer;

use App\DTO\TransactionDataContainer;
use App\Enums\VendingCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\ITokenIssuer;
use App\Models\Device;
use App\Models\Token;
use App\Plugins\DemoMeterManufacturer\Models\DemoMeterTransaction;

/**
 * Demo Meter Manufacturer API for demo purposes.
 * Returns random tokens for device charging operations without making real API calls.
 */
class DemoMeterManufacturerApi implements ITokenIssuer {
    public function __construct(
        private DemoMeterTransaction $demoMeterTransaction,
    ) {}

    /**
     * @return list<VendingCapability>
     */
    public function capabilities(): array {
        return [
            VendingCapability::AddCredit,
            VendingCapability::ClearCredit,
        ];
    }

    public function addCredit(TransactionDataContainer $transactionContainer): array {
        $tariff = $transactionContainer->tariff;
        $transactionContainer->chargeAmount += $transactionContainer->amount / $tariff->total_price;
        $transactionContainer->chargeUnit = Token::UNIT_KWH;
        $transactionContainer->chargeType = Token::TYPE_ENERGY;

        $energy = $transactionContainer->chargeAmount;

        // Generate a random token for demo purposes
        $randomToken = $this->generateRandomToken();

        // Record transaction like the real API
        $manufacturerTransaction = $this->demoMeterTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'demo_meter_transaction',
        ]);

        return [
            'token' => $randomToken,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $energy,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function unlockPaygo(TransactionDataContainer $transactionContainer): array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function resetCredit(Device $device): array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }

    /**
     * @return array{token: string, token_type: string, token_unit: null, token_amount: null}
     */
    public function clearCredit(Device $device): array {
        return [
            'token' => $this->generateRandomToken(),
            'token_type' => Token::TYPE_CLEAR_CREDIT,
            'token_unit' => null,
            'token_amount' => null,
        ];
    }

    /**
     * Generate a random token for demo purposes.
     */
    private function generateRandomToken(): string {
        $token = '';
        for ($i = 0; $i < 12; ++$i) {
            $token .= random_int(0, 9);
        }

        return $token;
    }
}
