<?php

namespace App\Services;

use App\Models\AccessRate\AccessRate;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<AccessRate>
 */
class AccessRateService implements IBaseService
{
    public function __construct(
        private AccessRate $accessRate
    ) {
    }

    public function getById(int $accessRateId): AccessRate
    {
        return $this->accessRate->newQuery()->find($accessRateId);
    }

    public function create(array $accessRateData): AccessRate
    {
        return $this->accessRate->newQuery()->create($accessRateData);
    }

    public function update($accessRate, $acessRateData): AccessRate
    {
        $accessRate->update($acessRateData);
        $accessRate->fresh();

        return $accessRate;
    }

    public function deleteByTariffId($meterTariffId)
    {
        $this->accessRate->newQuery()->where('tariff_id', $meterTariffId)->delete();
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection
    {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
