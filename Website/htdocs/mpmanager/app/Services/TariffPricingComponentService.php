<?php

namespace App\Services;

use App\Models\TariffPricingComponent;

class TariffPricingComponentService  implements IBaseService
{
    public function __construct(private TariffPricingComponent $tariffPricingComponent)
    {

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

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($data)
    {
        // TODO: Implement create() method.
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}
