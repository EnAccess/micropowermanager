<?php

namespace App\Lib\DummyManufacturerApis;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\Token;

/**
 * Dummy Kelin Meter API for demo purposes.
 * Returns random tokens for device charging operations.
 */
class DummyKelinMeterApi implements IManufacturerAPI {
    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $tariff = $transactionContainer->tariff;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / $tariff->total_price;

        $energy = (float) $transactionContainer->chargedEnergy;

        // Generate random token data for demo purposes
        $opType = rand(1, 2); // 1 or 2, like in the original

        if ($opType === 2) {
            // Energy token
            $payToken = $this->generateRandomEnergyToken();
            $token = sprintf('EnergyToken : %s', $payToken);
        } else {
            // Open tokens
            $openToken1 = $this->generateRandomOpenToken();
            $openToken2 = $this->generateRandomOpenToken();
            $token = sprintf('OpenToken1 : %s OpenToken2 : %s', $openToken1, $openToken2);
        }

        return [
            'token' => $token,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $energy,
        ];
    }

    /**
     * @param Device $device
     *
     * @return array<string,mixed>|null
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearDevice(Device $device): ?array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }

    /**
     * Generate a random energy token for demo purposes.
     */
    private function generateRandomEnergyToken(): string {
        $token = '';
        for ($i = 0; $i < 12; ++$i) {
            $token .= random_int(0, 9);
        }

        return $token;
    }

    /**
     * Generate a random open token for demo purposes.
     */
    private function generateRandomOpenToken(): string {
        return sprintf('%016d', rand(1000000000000000, 9999999999999999));
    }
}
