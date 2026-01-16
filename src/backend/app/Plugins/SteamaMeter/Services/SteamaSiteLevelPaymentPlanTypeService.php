<?php

namespace App\Plugins\SteamaMeter\Services;

use App\Plugins\SteamaMeter\Models\SteamaSiteLevelPaymentPlanType;

class SteamaSiteLevelPaymentPlanTypeService {
    public function __construct(private SteamaSiteLevelPaymentPlanType $paymentPlanType) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createPaymentPlans(): void {
        $paymentPlans = [
            'Credit Bundles',
            'Time-of-use Pricing',
            'Adaptive Per-kWh Tiers',
            'Hybrid Tariff & Monthly Usage Tiers',
        ];
        foreach ($paymentPlans as $value) {
            $paymentPlanType = $this->paymentPlanType->newQuery()->where('name', $value)->first();
            if (!$paymentPlanType) {
                $this->paymentPlanType->newQuery()->create([
                    'name' => $value,
                ]);
            }
        }
    }
}
