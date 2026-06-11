<?php

namespace App\Services;

use App\Models\SocialTariff;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-type SocialTariffData array{
 *     tariff_id?: int,
 *     name?: string,
 *     description?: string,
 *     rate?: float,
 *     effective_date?: string,
 *     expiry_date?: string|null
 * }
 *
 * @implements IBaseService<SocialTariff>
 */
class SocialTariffService implements IBaseService {
    /** @use HasCrudOperations<SocialTariff> */
    use HasCrudOperations;

    public function __construct(
        private SocialTariff $socialTariff,
    ) {}

    protected function crudModel(): SocialTariff {
        return $this->socialTariff;
    }

    /**
     * @param SocialTariff     $socialTariff
     * @param SocialTariffData $socialTariffData
     */
    public function update(Model $socialTariff, array $socialTariffData): SocialTariff {
        $socialTariff->update($socialTariffData);

        return $socialTariff->fresh();
    }

    public function deleteByTariffId(int $meterTariffId): void {
        $this->socialTariff->newQuery()->where('tariff_id', $meterTariffId)->delete();
    }
}
