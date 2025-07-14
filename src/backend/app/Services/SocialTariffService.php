<?php

namespace App\Services;

use App\Models\SocialTariff;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @phpstan-type SocialTariffData array{
 *     tariff_id?: int,
 *     name?: string,
 *     description?: string,
 *     rate?: float,
 *     effective_date?: string,
 *     expiry_date?: string|null
 * }
 */
class SocialTariffService implements IBaseService {
    public function __construct(
        private SocialTariff $socialTariff,
    ) {}

    /**
     * @param SocialTariffData $socialTariffData
     */
    public function create(array $socialTariffData): SocialTariff {
        return $this->socialTariff->newQuery()->create($socialTariffData);
    }

    public function getById(int $socialTariffId): SocialTariff {
        return $this->socialTariff->newQuery()->find($socialTariffId);
    }

    public function update($socialTariff, array $socialTariffData): SocialTariff {
        $socialTariff->update($socialTariffData);

        /** @var SocialTariff $freshTariff */
        $freshTariff = $socialTariff->fresh();

        return $freshTariff;
    }

    public function deleteByTariffId(int $meterTariffId): void {
        $this->socialTariff->newQuery()->where('tariff_id', $meterTariffId)->delete();
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, SocialTariff>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
