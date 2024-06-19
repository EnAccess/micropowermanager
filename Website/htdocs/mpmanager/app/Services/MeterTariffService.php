<?php

namespace App\Services;

use App\Models\Meter\MeterTariff;

class MeterTariffService implements IBaseService
{
    public function __construct(private MeterTariff $meterTariff)
    {
    }

    public function getById($meterTariffId)
    {
        return $this->meterTariff->newQuery()
            ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])
            ->findOrFail($meterTariffId);
    }

    public function create($meterTariffData)
    {
        return $this->meterTariff->newQuery()->create($meterTariffData);
    }

    public function update($meterTariff, $meterTariffData)
    {
        $meterTariff->update(
            $meterTariffData
        );
        $meterTariff->fresh();

        return $meterTariff;
    }

    public function delete($meterTariff)
    {
        return $meterTariff->delete();
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->meterTariff->newQuery()
                ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])->where('factor', 1)
                ->paginate($limit);
        }

        return $this->meterTariff->newQuery()
            ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])->where('factor', 1)
            ->get();
    }
}
