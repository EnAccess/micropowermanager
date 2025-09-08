<?php

namespace App\Lib\DummyManufacturerApis;

use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\Token;

/**
 * Dummy Calin Smart Meter API for demo purposes.
 * Returns random tokens for device charging operations.
 */
class DummyCalinSmartMeterApi implements IManufacturerAPI {
    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $tariff = $transactionContainer->tariff;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / $tariff->total_price;

        $energy = (float) $transactionContainer->chargedEnergy;

        // Generate a random token for demo purposes
        $randomToken = $this->generateRandomToken();

        return [
            'token' => $randomToken,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $energy,
        ];
    }

    /**
     * @param Device $device
     *
     * @return array<string,mixed>|null
     */
    public function clearDevice(Device $device): ?array {
        // Generate a random result code for demo purposes
        $randomResultCode = rand(200, 299);

        return [
            'result_code' => $randomResultCode,
        ];
    }

    /**
     * Generate a random token for demo purposes.
     */
    private function generateRandomToken(): string {
        return sprintf('%020d', rand(10000000000000000000, 99999999999999999999));
    }
}
