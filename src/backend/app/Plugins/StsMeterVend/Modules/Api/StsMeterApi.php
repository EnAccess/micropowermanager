<?php

namespace App\Plugins\StsMeterVend\Modules\Api;

use App\DTO\TransactionDataContainer;
use App\Enums\VendingCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerDeviceControl;
use App\Lib\ITokenIssuer;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Token;
use App\Plugins\StsMeterVend\Exceptions\StsVendRefusedException;
use App\Plugins\StsMeterVend\Models\StsMeter;
use App\Plugins\StsMeterVend\Models\StsTransaction;
use App\Plugins\StsMeterVend\Services\StsTokenIssuer;

class StsMeterApi implements ITokenIssuer, IManufacturerDeviceControl {
    private const string TOKEN_TYPE_TOP_UP = 'TOP_UP_KWH';
    private const string TOKEN_TYPE_CLEAR_CREDIT = 'CLEAR_CREDIT';
    // Above this the STS amount field loses its 0.1 kWh resolution and the engine
    // truncates, so the credited energy would fall short of the recorded amount.
    private const float MAX_KWH_PER_TOKEN = 1638.3;

    public function __construct(
        private StsTokenIssuer $issuer,
        private StsMeter $stsMeter,
    ) {}

    /**
     * @return list<VendingCapability>
     */
    public function capabilities(): array {
        return [
            VendingCapability::CreditToken,
            VendingCapability::ResetToken,
        ];
    }

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $meter = $this->meterOf($transactionContainer->device);
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
        $issue = $this->issuer->issue($this->meterOf($device), self::TOKEN_TYPE_CLEAR_CREDIT, null, null);

        return [
            'token' => $issue->token,
            'token_type' => Token::TYPE_RESET,
            'token_unit' => null,
            'token_amount' => null,
        ];
    }

    /**
     * A meter is mapped once its decoder key is recorded, since that is all the
     * engine needs to vend it.
     *
     * @return array{mapped: bool, device: array<string, mixed>|null}
     */
    public function getDeviceInfo(Device $device): array {
        $meter = $device->device;
        $stsMeter = $meter instanceof Meter
            ? $this->stsMeter->newQuery()->where('mpm_meter_id', $meter->id)->first()
            : null;

        if (!$stsMeter instanceof StsMeter) {
            return ['mapped' => false, 'device' => null];
        }

        return [
            'mapped' => true,
            'device' => [
                'base_date' => $stsMeter->base_date->value,
                'encryption_algorithm' => $stsMeter->encryption_algorithm->value,
                'key_expiry_number' => $stsMeter->key_expiry_number,
                'last_issued_at' => $stsMeter->last_issued_at?->toDateTimeString(),
            ],
        ];
    }

    /**
     * Any device can be moved to this plugin, so a device that is not a meter is
     * refused here, before anything is asked of the engine.
     */
    private function meterOf(?Device $device): Meter {
        $meter = $device?->device;

        if (!$meter instanceof Meter) {
            throw new StsVendRefusedException("STS vends meters only; device {$device?->device_serial} is a {$device?->device_type}.");
        }

        return $meter;
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
