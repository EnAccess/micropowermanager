<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\TransactionDataContainer;
use App\Enums\DeviceTokenUnit;
use App\Enums\DeviceType;
use App\Enums\ManufacturerMappingStatus;
use App\Events\NewLogEvent;
use App\Exceptions\Device\CreditPriceNotFoundException;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\DeviceCapabilities;
use App\Lib\DeviceMappingResult;
use App\Lib\IManufacturerAPI;
use App\Lib\IManufacturerDeviceControl;
use App\Models\AppliancePerson;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Token;
use App\Models\Transaction\Transaction;

/**
 * Everything MPM asks a manufacturer to do to a single device outside the payment
 * flow: read its status, issue a token on demand, switch its relay. It is the one
 * place that turns a device into a manufacturer API, so callers never touch
 * api_name or the container themselves.
 */
class DeviceControlService {
    public function __construct(
        private CashTransactionService $cashTransactionService,
        private AppliancePaymentService $appliancePaymentService,
    ) {}

    public function capabilities(Device $device): DeviceCapabilities {
        $api = $this->resolveApi($device);

        return new DeviceCapabilities(
            tokenGeneration: $api instanceof IManufacturerAPI,
            creditUnit: DeviceType::from($device->device_type)->creditUnit(),
        );
    }

    /**
     * Issues a token straight from the manufacturer, without a customer payment.
     * The credit is booked as an ad-hoc transaction because every manufacturer API
     * writes its own reference onto one, and because an operator granting credit
     * should stay visible next to the payments that granted the rest of it.
     *
     * Access rates, appliance installments and the minimum purchase amount are
     * deliberately not applied — this grants credit, it does not settle a debt.
     */
    public function generateToken(Device $device, float $amount, DeviceTokenUnit $unit, int $creatorId): Token {
        $api = $this->resolveApi($device);

        if (!$api instanceof IManufacturerAPI) {
            throw new ApiCallDoesNotSupportedException('The manufacturer of this device does not support token generation.');
        }

        $transaction = $this->cashTransactionService->createTransaction(
            $creatorId,
            $this->toCurrency($device, $amount, $unit),
            (string) ($device->person?->addresses()->first()->phone ?? ''),
            $device->device_serial,
            Transaction::TYPE_AD_HOC,
        );

        $tokenData = $api->chargeDevice(TransactionDataContainer::initialize($transaction));

        $token = Token::query()->make($tokenData);
        $token->device_id = $device->id;
        $token->transaction()->associate($transaction);
        $token->save();

        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $device,
            'action' => "Ad-hoc token generated for device {$device->device_serial}: {$token}",
        ]));

        return $token;
    }

    /**
     * Asks the device's manufacturer API whether the unit is mapped on the
     * manufacturer side, so users can diagnose unit remappings before a payment
     * fails. Manufacturers without a device management API report as unsupported.
     */
    public function verifyManufacturerMapping(Device $device): DeviceMappingResult {
        $api = $this->resolveApi($device);

        if (!$api instanceof IManufacturerDeviceControl) {
            return new DeviceMappingResult(supported: false);
        }

        $deviceInfo = $api->getDeviceInfo($device);

        return new DeviceMappingResult(supported: true, mapped: $deviceInfo['mapped'], device: $deviceInfo['device']);
    }

    /**
     * Runs the mapping check and persists its outcome on the device, so the
     * status is available in lists and exports without re-querying the
     * manufacturer.
     */
    public function refreshManufacturerMapping(Device $device): DeviceMappingResult {
        $result = $this->verifyManufacturerMapping($device);

        $device->update([
            'manufacturer_mapping_status' => $this->mappingStatus($result),
            'manufacturer_mapping_checked_at' => now(),
        ]);

        return $result;
    }

    private function resolveApi(Device $device): ?object {
        $manufacturer = $device->device->manufacturer;

        if (!$manufacturer || !$manufacturer->api_name) {
            return null;
        }

        try {
            return resolve($manufacturer->api_name);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Manufacturer APIs only vend against a currency amount, so a request made in
     * credit is priced at the same rate the API will divide by on the way back.
     * The credit finally issued is whatever the manufacturer returns — several
     * round their result — so callers must report the token, not the request.
     */
    private function toCurrency(Device $device, float $amount, DeviceTokenUnit $unit): float {
        if ($unit === DeviceTokenUnit::Currency) {
            return $amount;
        }

        $deviceType = DeviceType::from($device->device_type);

        if ($unit !== $deviceType->creditUnit()) {
            throw new CreditPriceNotFoundException("A {$deviceType->value} issues its credit in {$deviceType->creditUnit()->value}, not in {$unit->value}.");
        }

        $deviceModel = $device->device;

        if ($deviceModel instanceof Meter) {
            $tariff = $deviceModel->tariff()->first();

            if ($tariff === null) {
                throw new CreditPriceNotFoundException('This meter has no tariff, so an energy amount cannot be priced.');
            }

            return $amount * $tariff->total_price;
        }

        /** @var AppliancePerson|null $appliancePerson */
        $appliancePerson = $device->appliancePerson()->latest()->first();

        $dailyPrice = $appliancePerson === null
            ? 0.0
            : $this->appliancePaymentService->getDailyPrice($appliancePerson);

        if ($dailyPrice <= 0) {
            throw new CreditPriceNotFoundException('This device has no appliance plan with a daily price, so a number of days cannot be priced.');
        }

        return $amount * $dailyPrice;
    }

    private function mappingStatus(DeviceMappingResult $result): ManufacturerMappingStatus {
        if (!$result->supported) {
            return ManufacturerMappingStatus::Unsupported;
        }

        return $result->mapped
            ? ManufacturerMappingStatus::Mapped
            : ManufacturerMappingStatus::NotMapped;
    }
}
