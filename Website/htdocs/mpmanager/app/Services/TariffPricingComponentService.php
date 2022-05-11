<?php

namespace App\Services;

use App\Models\TariffPricingComponent;

class TariffPricingComponentService extends BaseService
{
    public function __construct(private TariffPricingComponent $tariffPricingComponent)
    {
        parent::__construct([$tariffPricingComponent]);
    }

    public function make($tariffPricingComponentData)
    {
        return $this->tariffPricingComponent->newQuery()->make($tariffPricingComponentData);
    }

    public function deleteByTariffId($meterTariffId)
    {
        $this->tariffPricingComponent->newQuery()->where('owner_type', 'meter_tariff')->where(
            'owner_id',
            $meterTariffId
        )->each(function($pricingComponent) {
            $pricingComponent->delete();
        });

    }
}