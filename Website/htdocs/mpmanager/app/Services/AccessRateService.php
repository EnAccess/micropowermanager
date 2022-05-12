<?php

namespace App\Services;


use App\Models\AccessRate\AccessRate;


class AccessRateService extends BaseService implements IBaseService
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

    public function update($accessRate, $acessRateData)
    {
        $accessRate->update($acessRateData);
        $accessRate->fresh();

        return $accessRate;
    }

    public function deleteByTariffId($meterTariffId)
    {
        $this->accessRate->newQuery()->where('tariff_id', $meterTariffId)->delete();
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