<?php

namespace App\Plugins\Prospect\Services;

use App\Models\Address\Address;
use App\Models\AppliancePerson;
use App\Models\DatabaseProxy;
use App\Models\Device;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\SubConnectionType;
use App\Models\User;
use Illuminate\Support\Carbon;

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
        $appliancePerson = $device->appliancePerson;
        $appliance = $device->appliance;

        $primaryAddress = $this->getPrimaryAddress($person);
        $latitude = null;
        $longitude = null;

        if ($primaryAddress instanceof Address) {
            [$latitude, $longitude] = $this->extractCoordinates($primaryAddress);
        }

        $deviceCategory = $this->mapDeviceCategory($device->device_type);
        $manufacturer = $deviceData->manufacturer ?? null;
        $usageCategory = $this->mapUsageCategory($device, $deviceData);
        $usageSubCategory = $this->mapUsageSubCategory($device, $deviceData, $usageCategory);

        $user = User::query()->first();
        $databaseProxy = app(DatabaseProxy::class);
        $companyId = $databaseProxy->findByEmail($user->email)->getCompanyId();

        $purchaseDate = null;
        if ($deviceCategory !== 'meter') {
            $purchaseDate = ($appliancePerson ? $appliancePerson->created_at : null) ?? $device->created_at;
        }

        $installationDate = $device->created_at?->format('Y-m-d');

        $paymentPlanData = [
            'payment_plan_amount_financed_principal' => null,
            'payment_plan_amount_financed_total' => null,
            'payment_plan_cash_price' => $appliancePerson?->total_cost,
            'payment_plan_installment_amount' => null,
            'payment_plan_installment_period_days' => null,
            'payment_plan_days_financed' => null,
            'payment_plan_days_down_payment' => null,
            'paid_off_date' => null,
        ];

        if ($deviceCategory === 'solar_home_system') {
            $paymentPlanData = array_merge(
                $paymentPlanData,
                $this->buildSolarHomeSystemPaymentPlanData($device, $appliancePerson)
            );
        }

        return [
            'customer_external_id' => $person?->id,
            'manufacturer' => $manufacturer ? $manufacturer->name : 'Unknown',
            'serial_number' => $deviceData->serial_number ?? '',
            'seller_agent_external_id' => ($appliancePerson?->creator_type === 'agent') ? $appliancePerson->creator_id : null,
            'installer_agent_external_id' => null,
            'product_common_id' => $appliance?->id ? (string) $appliance->id : null,
            'device_external_id' => (string) $device->id,
            'parent_customer_external_id' => (string) $primaryAddress?->city?->mini_grid_id,
            'account_external_id' => $companyId,
            'battery_capacity_wh' => null,
            'usage_category' => $usageCategory,
            'usage_sub_category' => $usageSubCategory,
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
            'payment_plan_amount_down_payment' => $appliancePerson->down_payment ?? null,
            'payment_plan_cash_price' => $paymentPlanData['payment_plan_cash_price'],
            'payment_plan_currency' => MainSettings::query()->first()?->currency,
            'payment_plan_installment_amount' => $paymentPlanData['payment_plan_installment_amount'],
            'payment_plan_number_of_installments' => $appliancePerson->rate_count ?? null,
            'payment_plan_installment_period_days' => $paymentPlanData['payment_plan_installment_period_days'],
            'payment_plan_days_financed' => $paymentPlanData['payment_plan_days_financed'],
            'payment_plan_days_down_payment' => $paymentPlanData['payment_plan_days_down_payment'],
            'payment_plan_category' => $appliancePerson ? 'paygo' : null,
            'purchase_date' => $purchaseDate?->format('Y-m-d'),
            'installation_date' => $installationDate,
            'repossession_date' => null,
            'paid_off_date' => $paymentPlanData['paid_off_date'],
            'repossession_category' => null,
            'write_off_date' => null,
            'write_off_reason' => null,
            'is_test' => false,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'country' => $primaryAddress?->city?->country?->country_code,
            'location_area_1' => null,
            'location_area_2' => null,
            'location_area_3' => null,
            'location_area_4' => null,
            'location_area_5' => null,
        ];
    }

    /**
     * Build payment plan data for solar home system devices.
     *
     * @return array<string, float|int|string|null>
     */
    private function buildSolarHomeSystemPaymentPlanData(Device $device, ?AppliancePerson $appliancePerson): array {
        if (!$appliancePerson instanceof AppliancePerson) {
            return [];
        }

        $totalCost = $appliancePerson->total_cost;
        $downPayment = $appliancePerson->down_payment ?? 0.0;
        $rateCount = $appliancePerson->rate_count ?? null;
        $rates = $appliancePerson->rates()->oldest('due_date')->get();

        $firstRate = $rates->first();
        $secondRate = $rates->skip(1)->first();
        $firstDueDate = $this->parseDate($firstRate?->due_date);
        $secondDueDate = $this->parseDate($secondRate?->due_date);

        $installmentPeriodDays = null;
        if ($firstDueDate && $secondDueDate) {
            $installmentPeriodDays = $firstDueDate->diffInDays($secondDueDate);
        }

        $startDate = $this->parseDate($appliancePerson->first_payment_date) ?? $device->created_at;
        $daysDownPayment = null;
        if ($firstDueDate && $startDate) {
            $daysDownPayment = $startDate->diffInDays($firstDueDate);
        }

        $financedPrincipal = null;
        if ($appliancePerson->down_payment !== null) {
            $financedPrincipal = max($totalCost - $appliancePerson->down_payment, 0.0);
        }

        $financedTotal = max($totalCost - $downPayment, 0.0);

        $installmentAmount = null;
        if ($rateCount && $rateCount > 0) {
            $installmentAmount = $financedTotal / $rateCount;
        }

        $daysFinanced = null;
        if ($installmentPeriodDays !== null && $rateCount) {
            $daysFinanced = $installmentPeriodDays * $rateCount;
        }

        $paidOffDate = null;
        $lastRate = $rates->last();
        if ($lastRate && (float) $lastRate->remaining <= 0) {
            $lastDueDate = $this->parseDate($lastRate->due_date);
            $paidOffDate = $lastDueDate?->format('Y-m-d');
        }

        return [
            'payment_plan_amount_financed_principal' => $financedPrincipal,
            'payment_plan_amount_financed_total' => $financedTotal,
            'payment_plan_cash_price' => $totalCost,
            'payment_plan_installment_amount' => $installmentAmount,
            'payment_plan_installment_period_days' => $installmentPeriodDays,
            'payment_plan_days_financed' => $daysFinanced,
            'payment_plan_days_down_payment' => $daysDownPayment,
            'paid_off_date' => $paidOffDate,
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
            'e_bike' => 'other_production_use',
            default => 'other_production_use',
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

    /**
     * Map sub connection type to Prospect usage_sub_category for non-household connections.
     */
    private function mapUsageSubCategory(Device $device, mixed $deviceData, ?string $usageCategory): ?string {
        if ($usageCategory === 'household' || $usageCategory === null) {
            return null;
        }

        if ($device->device_type === 'meter' && $deviceData instanceof Meter) {
            $subConnectionType = SubConnectionType::query()
                ->where('connection_type_id', $deviceData->connection_type_id)
                ->first();

            return $subConnectionType?->name;
        }

        return null;
    }

    private function parseDate(mixed $value): ?Carbon {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
