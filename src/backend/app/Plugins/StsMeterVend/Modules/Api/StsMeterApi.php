<?php

namespace App\Plugins\StsMeterVend\Modules\Api;

use App\DTO\TransactionDataContainer;
use App\Enums\ManufacturerCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerDeviceControl;
use App\Lib\IVendingProvider;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Token;
use App\Plugins\StsMeterVend\Exceptions\StsVendRefusedException;
use App\Plugins\StsMeterVend\Models\StsTransaction;
use App\Plugins\StsMeterVend\Services\StsTokenIssuer;

class StsMeterApi implements IVendingProvider, IManufacturerDeviceControl {
    public const string VENDING_PROVIDER = 'native_sts';
    private const string TOKEN_TYPE_TOP_UP = 'TOP_UP_KWH';
    private const string TOKEN_TYPE_CLEAR_CREDIT = 'CLEAR_CREDIT';
    // Above this the STS amount field loses its 0.1 kWh resolution and the engine
    // truncates, so the credited energy would fall short of the recorded amount.
    private const float MAX_KWH_PER_TOKEN = 1638.3;

    public function __construct(
        private StsTokenIssuer $issuer,
    ) {}

    /**
     * @return list<ManufacturerCapability>
     */
    public function capabilities(): array {
        return [
            ManufacturerCapability::CreditToken,
            ManufacturerCapability::ResetToken,
        ];
    }

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $meter = $transactionContainer->device->device;
        $previousIssue = $this->issuer->previousIssue($transactionContainer->transaction);
        $kwh = $previousIssue instanceof StsTransaction
            ? (float) $previousIssue->kwh
            : $this->creditableKwh($transactionContainer, $meter);

        $issue = $this->issuer->issue($meter, self::TOKEN_TYPE_TOP_UP, $kwh, $transactionContainer->transaction);

        $transactionContainer->chargeAmount += $kwh;
        $transactionContainer->chargeUnit = Token::UNIT_KWH;
        $transactionContainer->chargeType = Token::TYPE_ENERGY;

        return [
            'token' => $issue->token,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $kwh,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function unlockDevice(TransactionDataContainer $transactionContainer): array {
        throw new ApiCallDoesNotSupportedException('STS meters have no unlock token.');
    }

    /**
     * @return array<string,mixed>|null
     */
    public function clearDevice(Device $device): ?array {
        $issue = $this->issuer->issue($device->device, self::TOKEN_TYPE_CLEAR_CREDIT, null, null);

        return [
            'token' => $issue->token,
            'token_type' => Token::TYPE_RESET,
            'token_unit' => null,
            'token_amount' => null,
        ];
    }

    public function isConfiguredFor(Meter $meter): bool {
        return $this->issuer->hasKey($meter);
    }

    /**
     * @return array{mapped: bool, device: array<string, mixed>|null}
     */
    public function getDeviceInfo(Device $device): array {
        $meter = $device->device;
        $keyPresent = $meter instanceof Meter && $this->isConfiguredFor($meter);

        return [
            'mapped' => $keyPresent,
            'device' => ['serial' => $device->device_serial, 'key_present' => $keyPresent],
        ];
    }

    private function creditableKwh(TransactionDataContainer $transactionContainer, Meter $meter): float {
        $pricePerKwh = $transactionContainer->tariff->total_price;
        if ($pricePerKwh <= 0) {
            throw new StsVendRefusedException("The tariff of meter {$meter->serial_number} has no price per kWh.");
        }

        // STS encodes credit in tenths of a kWh; rounding up here keeps the customer
        // from receiving less than they paid for and keeps the engine's own rounding out
        // of the picture.
        $kwh = ceil(($transactionContainer->amount / $pricePerKwh) * 10) / 10;

        if ($kwh <= 0) {
            throw new StsVendRefusedException("Amount is below the smallest creditable unit for meter {$meter->serial_number}.");
        }

        if ($kwh > self::MAX_KWH_PER_TOKEN) {
            throw new StsVendRefusedException("A single token for meter {$meter->serial_number} can carry at most ".self::MAX_KWH_PER_TOKEN.' kWh.');
        }

        return $kwh;
    }
}
