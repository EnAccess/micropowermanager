<?php

namespace App\Services;

use App\Models\TariffPricingComponent;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<TariffPricingComponent>
 */
class TariffPricingComponentService implements IBaseService {
    /** @use HasCrudOperations<TariffPricingComponent> */
    use HasCrudOperations;

    public function __construct(
        private TariffPricingComponent $tariffPricingComponent,
    ) {}

    protected function crudModel(): TariffPricingComponent {
        return $this->tariffPricingComponent;
    }

    /**
     * @param array<string, mixed> $tariffPricingComponentData
     */
    public function make(array $tariffPricingComponentData): TariffPricingComponent {
        return $this->tariffPricingComponent->newQuery()->make($tariffPricingComponentData);
    }

    public function deleteByTariffId(int $TariffId): void {
        $this->tariffPricingComponent->newQuery()->where('owner_type', 'tariff')->where(
            'owner_id',
            $TariffId
        )->each(function ($pricingComponent) {
            $pricingComponent->delete();
        });
    }
}
