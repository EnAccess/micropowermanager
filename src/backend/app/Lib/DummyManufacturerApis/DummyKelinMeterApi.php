<?php

namespace App\Lib\DummyManufacturerApis;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\Token;
use Inensus\KelinMeter\Models\KelinTransaction;

/**
 * Dummy Kelin Meter API for demo purposes.
 * Returns random tokens for device charging operations.
 */
class DummyKelinMeterApi implements IManufacturerAPI {
    public function __construct(
        private KelinTransaction $kelinTransaction,
    ) {}

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $tariff = $transactionContainer->tariff;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / $tariff->total_price;

        $energy = $transactionContainer->chargedEnergy;

        $meter = $transactionContainer->device->device;
        $amount = $transactionContainer->totalAmount;

        // Generate random token data for demo purposes
        $opType = random_int(1, 2); // 1 or 2, like in the original
        $payKWH = $energy;
        $openToken1 = $this->generateRandomOpenToken();
        $openToken2 = $this->generateRandomOpenToken();
        $payToken = $this->generateRandomEnergyToken();

        // Record transaction like the real API with all the fields
        $manufacturerTransaction = $this->kelinTransaction->newQuery()->create([
            'meter_serial' => $meter->serial_number,
            'amount' => $amount,
            'op_type' => $opType,
            'pay_kwh' => (string) $payKWH,
            'open_token_1' => $openToken1,
            'open_token_2' => $openToken2,
            'pay_token' => $payToken,
        ]);

        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'kelin_transaction',
        ]);

        $token = $opType === 2 ? sprintf(
            'EnergyToken : %s',
            $payToken
        ) :
            sprintf(
                'OpenToken1 : %s OpenToken2 : %s',
                $openToken1,
                $openToken2
            );

        return [
            'token' => $token,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
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
        return sprintf('%016d', random_int(1000000000000000, 9999999999999999));
    }
}
