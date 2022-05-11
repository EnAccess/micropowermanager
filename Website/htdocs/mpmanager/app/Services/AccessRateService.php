<?php

namespace App\Services;


use App\Models\AccessRate\AccessRate;

class AccessRateService extends BaseService
{

    public function __construct(private AccessRate $accessRate)
    {
        parent::__construct([$accessRate]);
    }

    public function getById($accessRateId)
    {
        return $this->accessRate->newQuery()->find($accessRateId);
    }
    public function create($accessRateData)
    {
        return $this->accessRate->newQuery()->create($accessRateData);
    }

    public function update($accessRate, $accessRateData)
    {
        $accessRate->update($accessRateData);

        return $accessRate;
   }

    public function deleteByTariffId($meterTariffId)
    {
        $this->accessRate->newQuery()->where('tariff_id', $meterTariffId)->delete();
   }
}