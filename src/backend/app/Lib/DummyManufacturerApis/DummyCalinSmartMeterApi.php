<?php

namespace App\Lib\DummyManufacturerApis;

use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\Token;
use Inensus\CalinSmartMeter\Models\CalinSmartTransaction;

/**
 * Dummy Calin Smart Meter API for demo purposes.
 * Returns random tokens for device charging operations.
 */
class DummyCalinSmartMeterApi implements IManufacturerAPI {
    public function __construct(
        private CalinSmartTransaction $calinSmartTransaction,
    ) {}

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $tariff = $transactionContainer->tariff;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / $tariff->total_price;

        $energy = $transactionContainer->chargedEnergy;

        // Generate a random token for demo purposes
        $randomToken = $this->generateRandomToken();

        // Record transaction like the real API
        $manufacturerTransaction = $this->calinSmartTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'calin_smart_transaction',
        ]);

        return [
            'token' => $randomToken,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $energy,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function clearDevice(Device $device): ?array {
        // Generate a random result code for demo purposes
        $randomResultCode = random_int(200, 299);

        return [
            'result_code' => $randomResultCode,
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
