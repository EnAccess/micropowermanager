<?php

namespace App\Services;

use App\Models\Meter\MeterTariff;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<MeterTariff>
 */
class MeterTariffService implements IBaseService
{
    public function __construct(
        private MeterTariff $meterTariff
    ) {
    }

    public function getById(int $meterTariffId): MeterTariff
    {
        return $this->meterTariff->newQuery()
            ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])
            ->findOrFail($meterTariffId);
    }

    public function create(array $meterTariffData): MeterTariff
    {
        return $this->meterTariff->newQuery()->create($meterTariffData);
    }

    public function update($meterTariff, array $meterTariffData): MeterTariff
    {
        $meterTariff->update(
            $meterTariffData
        );
        $meterTariff->fresh();

        return $meterTariff;
    }

    public function delete($meterTariff): ?bool
    {
        return $meterTariff->delete();
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator
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
