<?php

namespace App\Services;

use App\Models\Meter\Meter;
use App\Models\Tariff;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Tariff>
 */
class TariffService implements IBaseService {
    /** @use HasCrudOperations<Tariff> */
    use HasCrudOperations;

    public function __construct(
        private Tariff $tariff,
        private Meter $meter,
    ) {}

    protected function crudModel(): Tariff {
        return $this->tariff;
    }

    public function getById(int $tariffId): Tariff {
        return $this->tariff->newQuery()
            ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])
            ->findOrFail($tariffId);
    }

    /**
     * @return Collection<int, Tariff>|LengthAwarePaginator<int, Tariff>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->tariff->newQuery()
                ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])->where('factor', 1)
                ->paginate($limit);
        }

        return $this->tariff->newQuery()
            ->with(['accessRate', 'pricingComponent', 'socialTariff', 'tou'])->where('factor', 1)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function getCountById(int $tariffId): array {
        $count = $this->meter->newQuery()->where('tariff_id', $tariffId)->count();

        return ['count' => $count];
    }

    public function changeMetersTariff(int $tariffIdFrom, int $tariffIdTo): int {
        return $this->meter->newQuery()->where('tariff_id', $tariffIdFrom)
            ->update(['tariff_id' => $tariffIdTo]);
    }

    public function changeMeterTariff(string $meterSerial, int $tariffId): Meter {
        $meter = $this->meter->newQuery()->where('serial_number', $meterSerial)->firstOrFail();
        $meter->tariff_id = $tariffId;
        $meter->update();
        $meter->save();

        return $meter;
    }
}
