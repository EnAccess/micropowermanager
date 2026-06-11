<?php

namespace App\Services;

use App\Models\AccessRate\AccessRate;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<AccessRate>
 */
class AccessRateService implements IBaseService {
    /** @use HasCrudOperations<AccessRate> */
    use HasCrudOperations;

    public function __construct(
        private AccessRate $accessRate,
    ) {}

    protected function crudModel(): AccessRate {
        return $this->accessRate;
    }

    public function deleteByTariffId(int $meterTariffId): void {
        $this->accessRate->newQuery()->where('tariff_id', $meterTariffId)->delete();
    }
}
