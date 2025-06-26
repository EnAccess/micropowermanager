<?php

namespace App\Services;

use App\Models\TariffPricingComponent;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<TariffPricingComponent>
 */
class TariffPricingComponentService implements IBaseService {
    public function __construct(
        private TariffPricingComponent $tariffPricingComponent,
    ) {}

    /**
     * @param array<string, mixed> $tariffPricingComponentData
     */
    public function make(array $tariffPricingComponentData): TariffPricingComponent {
        return $this->tariffPricingComponent->newQuery()->make($tariffPricingComponentData);
    }

    public function deleteByTariffId(int $meterTariffId): void {
        $this->tariffPricingComponent->newQuery()->where('owner_type', 'meter_tariff')->where(
            'owner_id',
            $meterTariffId
        )->each(function ($pricingComponent) {
            $pricingComponent->delete();
        });
    }

    public function getById(int $id): TariffPricingComponent {
        throw new \Exception('Method getById() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): TariffPricingComponent {
        throw new \Exception('Method create() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): TariffPricingComponent {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, TariffPricingComponent>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
