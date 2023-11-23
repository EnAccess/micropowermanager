<?php

namespace App\Services;

use App\Models\SocialTariff;

class SocialTariffService implements IBaseService
{
    public function __construct(private SocialTariff $socialTariff)
    {
    }

    public function create($socialTariffData)
    {
        return $this->socialTariff->newQuery()->create($socialTariffData);
    }

    public function getById($socialTariffId)
    {
        return $this->socialTariff->newQuery()->find($socialTariffId);
    }

    public function update($socialTariff, $socialTariffData)
    {
        $socialTariff->update($socialTariffData);
        $socialTariff->fresh();

        return $socialTariff;
    }

    public function deleteByTariffId($meterTariffId)
    {
        $this->socialTariff->newQuery()->where('tariff_id', $meterTariffId)->delete();
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
