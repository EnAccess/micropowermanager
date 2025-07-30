<?php

namespace App\Services;

use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<MeterTariff>
 */
class MeterTariffService implements IBaseService {
    public function __construct(
        private MeterTariff $meterTariff,
        private Meter $meter,
    ) {}

    public function getById(int $meterTariffId): MeterTariff {
        return $this->meterTariff->newQuery()
            ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])
            ->findOrFail($meterTariffId);
    }

    /**
     * @param array<string, mixed> $meterTariffData
     */
    public function create(array $meterTariffData): MeterTariff {
        return $this->meterTariff->newQuery()->create($meterTariffData);
    }

    /**
     * @param array<string, mixed> $meterTariffData
     */
    public function update($meterTariff, array $meterTariffData): MeterTariff {
        $meterTariff->update(
            $meterTariffData
        );
        $meterTariff->fresh();

        return $meterTariff;
    }

    public function delete($meterTariff): ?bool {
        return $meterTariff->delete();
    }

    /**
     * @return Collection<int, MeterTariff>|LengthAwarePaginator<MeterTariff>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->meterTariff->newQuery()
                ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])->where('factor', 1)
                ->paginate($limit);
        }

        return $this->meterTariff->newQuery()
            ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])->where('factor', 1)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function getCountById(int $meterTariffId): array {
        $count = $this->meter->newQuery()->where('tariff_id', $meterTariffId)->count();

        return ['count' => $count];
    }

    public function changeMetersTariff(int $meterTariffIdFrom, int $meterTariffIdTo): int {
        return $this->meter->newQuery()->where('tariff_id', $meterTariffIdFrom)
            ->update(['tariff_id' => $meterTariffIdTo]);
    }

    public function changeMeterTariff(string $meterSerial, int $tariffId): Meter {
        $meter = $this->meter->newQuery()->where('serial_number', $meterSerial)->firstOrFail();
        $meter->tariff_id = $tariffId;
        $meter->update();
        $meter->save();

        return $meter;
    }
}
