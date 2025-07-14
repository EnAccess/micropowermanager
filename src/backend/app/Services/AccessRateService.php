<?php

namespace App\Services;

use App\Models\AccessRate\AccessRate;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<AccessRate>
 */
class AccessRateService implements IBaseService {
    public function __construct(
        private AccessRate $accessRate,
    ) {}

    public function getById(int $accessRateId): AccessRate {
        return $this->accessRate->newQuery()->find($accessRateId);
    }

    /**
     * @param array<string, mixed> $accessRateData
     */
    public function create(array $accessRateData): AccessRate {
        return $this->accessRate->newQuery()->create($accessRateData);
    }

    /**
     * @param array<string, mixed> $acessRateData
     */
    public function update($accessRate, array $acessRateData): AccessRate {
        $accessRate->update($acessRateData);
        $accessRate->fresh();

        return $accessRate;
    }

    public function deleteByTariffId(int $meterTariffId): void {
        $this->accessRate->newQuery()->where('tariff_id', $meterTariffId)->delete();
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, AccessRate>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
