<?php

namespace App\Services;

use App\Models\SocialTariff;

class SocialTariffService extends BaseService
{
    public function __construct(private SocialTariff $socialTariff)
    {
        parent::__construct([$socialTariff]);
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

         return $socialTariff;
    }

    public function deleteByTariffId($meterTariffId)
    {
        $this->socialTariff->newQuery()->where('tariff_id', $meterTariffId)->delete();
    }
}