<?php

namespace Inensus\Prospect\Services;

use App\Models\Address\Address;
use App\Models\DatabaseProxy;
use App\Models\Device;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\User;

class ProspectInstallationTransformer {
    /**
     * Transform a Device model into a Prospect installation array.
     *
     * @return array<string, mixed>
     */
    public function transform(Device $device): array {
        if (!$device->device()->exists()) {
            throw new \InvalidArgumentException('Device must have an underlying device model.');
        }

        $deviceData = $device->device;
        $deviceData->load('manufacturer');

        $person = $device->person()->first();
        $assetPerson = $device->assetPerson;
        $appliance = $device->appliance;
        $customerIdentifier = $person ? trim(($person->name ?? '').' '.($person->surname ?? '')) : 'Unknown Customer';

        $primaryAddress = $this->getPrimaryAddress($person);
        $latitude = null;
        $longitude = null;

        if ($primaryAddress instanceof Address) {
            [$latitude, $longitude] = $this->extractCoordinates($primaryAddress);
        }

        $deviceCategory = $this->mapDeviceCategory($device->device_type);
        $manufacturer = $deviceData->manufacturer ?? null;
        $usageCategory = $this->mapUsageCategory($device, $deviceData);

        $user = User::query()->first();
        $databaseProxy = app(DatabaseProxy::class);
        $companyId = $databaseProxy->findByEmail($user->email)->getCompanyId();

        return [
            'customer_external_id' => $person?->id,
            'manufacturer' => $manufacturer ? $manufacturer->name : 'Unknown',
            'serial_number' => $deviceData->serial_number ?? '',
            'seller_agent_external_id' => $customerIdentifier,
            'installer_agent_external_id' => $customerIdentifier,
            'product_common_id' => $appliance?->id ? (string) $appliance->id : null,
            'device_external_id' => (string) $device->id,
            'parent_customer_external_id' => (string) $primaryAddress?->city?->mini_grid_id,
            'account_external_id' => $companyId,
            'battery_capacity_wh' => null,
            'usage_category' => $usageCategory,
            'usage_sub_category' => null,
            'device_category' => $deviceCategory,
            'ac_input_source' => null,
            'dc_input_source' => ($deviceCategory === 'solar_home_system') ? 'solar' : null,
            'firmware_version' => null,
            'model' => null,
            'primary_use' => null,
            'rated_power_w' => null,
            'pv_power_w' => null,
            'site_name' => $primaryAddress?->street,
            'payment_plan_amount_financed_principal' => null,
            'payment_plan_amount_financed_interest' => null,
            'payment_plan_amount_financed_total' => null,
            'payment_plan_amount_down_payment' => $assetPerson->down_payment ?? null,
            'payment_plan_cash_price' => $assetPerson->total_cost ?? null,
            'payment_plan_currency' => MainSettings::query()->first()?->currency,
            'payment_plan_installment_amount' => null,
            'payment_plan_number_of_installments' => $assetPerson->rate_count ?? null,
            'payment_plan_installment_period_days' => null,
            'payment_plan_days_financed' => null,
            'payment_plan_days_down_payment' => null,
            'payment_plan_category' => $assetPerson ? 'paygo' : null,
            'purchase_date' => $device->created_at->format('Y-m-d'),
            'installation_date' => $device->created_at->format('Y-m-d'),
            'repossession_date' => null,
            'paid_off_date' => null,
            'repossession_category' => null,
            'write_off_date' => null,
            'write_off_reason' => null,
            'is_test' => false,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'country' => $primaryAddress?->city?->country?->country_code,
            'location_area_1' => $primaryAddress?->city?->country?->country_name,
            'location_area_2' => $primaryAddress?->city?->name,
            'location_area_3' => null,
            'location_area_4' => null,
            'location_area_5' => null,
        ];
    }

    /**
     * Get the primary address for a person.
     */
    private function getPrimaryAddress(?Person $person): ?Address {
        if (!$person || !$person->addresses()->exists()) {
            return null;
        }

        return $person->addresses->where('is_primary', 1)->first() ?: $person->addresses->first();
    }

    /**
     * Extract latitude and longitude from an address geo information.
     *
     * @return array{0: float|null, 1: float|null}
     */
    private function extractCoordinates(?Address $address): array {
        $latitude = null;
        $longitude = null;

        $geoInfo = $address?->geo;
        if ($geoInfo && $geoInfo->points) {
            $coordinates = explode(',', $geoInfo->points);
            if (count($coordinates) >= 2) {
                $latitude = is_numeric($coordinates[0]) ? (float) trim($coordinates[0]) : null;
                $longitude = is_numeric($coordinates[1]) ? (float) trim($coordinates[1]) : null;
            }
        }

        return [$latitude, $longitude];
    }

    /**
     * Map device type to device category.
     */
    private function mapDeviceCategory(string $deviceType): string {
        return match ($deviceType) {
            'meter' => 'meter',
            'solar_home_system' => 'solar_home_system',
            default => 'other',
        };
    }

    /**
     * Map connection type to Prospect usage_category enum (household, institutional, commercial).
     */
    private function mapUsageCategory(Device $device, mixed $deviceData): ?string {
        if ($device->device_type === 'meter' && $deviceData instanceof Meter) {
            $deviceData->load('connectionType');
            $category = $deviceData->connectionType?->name;
        } else {
            return null;
        }

        if (empty($category)) {
            return null;
        }

        $normalized = strtolower(trim($category));

        if (str_contains($normalized, 'household')) {
            return 'household';
        }

        if (str_contains($normalized, 'institutional') || str_contains($normalized, 'institution')) {
            return 'institutional';
        }

        if (str_contains($normalized, 'commercial')) {
            return 'commercial';
        }

        return null;
    }
}
