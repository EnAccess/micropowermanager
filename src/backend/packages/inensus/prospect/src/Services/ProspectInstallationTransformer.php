<?php

namespace Inensus\Prospect\Services;

use App\Models\Address\Address;
use App\Models\AssetPerson;
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

        $paymentPlanData = [
            'payment_plan_amount_financed_principal' => null,
            'payment_plan_amount_financed_total' => null,
            'payment_plan_cash_price' => $assetPerson?->total_cost,
            'payment_plan_installment_amount' => null,
            'payment_plan_installment_period_days' => null,
            'payment_plan_days_financed' => null,
            'payment_plan_days_down_payment' => null,
        ];

        if ($deviceCategory === 'solar_home_system') {
            $paymentPlanData = array_merge(
                $paymentPlanData,
                $this->buildSolarHomeSystemPaymentPlanData($device, $assetPerson)
            );
        }

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
            'payment_plan_amount_financed_principal' => $paymentPlanData['payment_plan_amount_financed_principal'],
            'payment_plan_amount_financed_interest' => null,
            'payment_plan_amount_financed_total' => $paymentPlanData['payment_plan_amount_financed_total'],
            'payment_plan_amount_down_payment' => $assetPerson->down_payment ?? null,
            'payment_plan_cash_price' => $paymentPlanData['payment_plan_cash_price'],
            'payment_plan_currency' => MainSettings::query()->first()?->currency,
            'payment_plan_installment_amount' => $paymentPlanData['payment_plan_installment_amount'],
            'payment_plan_number_of_installments' => $assetPerson->rate_count ?? null,
            'payment_plan_installment_period_days' => $paymentPlanData['payment_plan_installment_period_days'],
            'payment_plan_days_financed' => $paymentPlanData['payment_plan_days_financed'],
            'payment_plan_days_down_payment' => $paymentPlanData['payment_plan_days_down_payment'],
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
     * Build payment plan data for solar home system devices.
     *
     * @return array<string, float|int|null>
     */
    private function buildSolarHomeSystemPaymentPlanData(Device $device, ?AssetPerson $assetPerson): array {
        if (!$assetPerson) {
            return [];
        }

        $totalCost = $assetPerson->total_cost;
        $downPayment = $assetPerson->down_payment ?? 0.0;
        $rateCount = $assetPerson->rate_count ?? null;
        $rates = $assetPerson->rates()->orderBy('due_date')->get();

        $firstRate = $rates->first();
        $secondRate = $rates->skip(1)->first();
        $firstDueDate = $firstRate?->due_date;
        $secondDueDate = $secondRate?->due_date;

        $installmentPeriodDays = null;
        if ($firstDueDate && $secondDueDate) {
            $installmentPeriodDays = $firstDueDate->diffInDays($secondDueDate);
        }

        $startDate = $assetPerson->first_payment_date ?? $device->created_at;
        $daysDownPayment = null;
        if ($firstDueDate && $startDate) {
            $daysDownPayment = $startDate->diffInDays($firstDueDate);
        }

        $financedPrincipal = null;
        if ($totalCost !== null && $assetPerson->down_payment !== null) {
            $financedPrincipal = max($totalCost - $assetPerson->down_payment, 0.0);
        }

        $financedTotal = $totalCost !== null ? max($totalCost - $downPayment, 0.0) : null;

        $installmentAmount = null;
        if ($financedTotal !== null && $rateCount && $rateCount > 0) {
            $installmentAmount = $financedTotal / $rateCount;
        }

        $daysFinanced = null;
        if ($installmentPeriodDays !== null && $rateCount) {
            $daysFinanced = $installmentPeriodDays * $rateCount;
        }

        return [
            'payment_plan_amount_financed_principal' => $financedPrincipal,
            'payment_plan_amount_financed_total' => $financedTotal,
            'payment_plan_cash_price' => $totalCost,
            'payment_plan_installment_amount' => $installmentAmount,
            'payment_plan_installment_period_days' => $installmentPeriodDays,
            'payment_plan_days_financed' => $daysFinanced,
            'payment_plan_days_down_payment' => $daysDownPayment,
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
