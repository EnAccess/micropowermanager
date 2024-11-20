<?php

namespace App\Services;

use App\Models\SocialTariff;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<SocialTariff>
 */
class SocialTariffService implements IBaseService
{
    public function __construct(
        private SocialTariff $socialTariff,
    ) {
    }

    public function create(array $socialTariffData): SocialTariff
    {
        return $this->socialTariff->newQuery()->create($socialTariffData);
    }

    public function getById(int $socialTariffId): SocialTariff
    {
        return $this->socialTariff->newQuery()->find($socialTariffId);
    }

    public function update($socialTariff, array $socialTariffData): SocialTariff
    {
        $socialTariff->update($socialTariffData);
        $socialTariff->fresh();

        return $socialTariff;
    }

    public function deleteByTariffId($meterTariffId)
    {
        $this->socialTariff->newQuery()->where('tariff_id', $meterTariffId)->delete();
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
