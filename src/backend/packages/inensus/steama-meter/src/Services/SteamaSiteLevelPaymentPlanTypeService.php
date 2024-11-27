<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Models\SteamaSiteLevelPaymentPlanType;

class SteamaSiteLevelPaymentPlanTypeService {
    private $paymentPlanType;

    public function __construct(SteamaSiteLevelPaymentPlanType $paymentPlanTypeModel) {
        $this->paymentPlanType = $paymentPlanTypeModel;
    }

    /**
     * This function uses one time on installation of the package.
     */
    public function createPaymentPlans() {
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
