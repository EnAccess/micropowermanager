<?php

namespace App\Services;

use App\Models\Meter\Meter;
use App\Models\Meter\MeterConsumption;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<MeterConsumption>
 */
class MeterConsumptionService implements IBaseService {
    public function __construct(
        private MeterConsumption $meterConsumption,
    ) {}

    /**
     * @return Collection<int, MeterConsumption>
     */
    public function getByMeter(Meter $meter, string $start, string $end): Collection {
        return $this->meterConsumption->newQuery()
            ->where('meter_id', $meter->id)->whereBetween(
                'reading_date',
                [$start, $end]
            )->orderBy('reading_date')->get();
    }

    public function getById(int $id): MeterConsumption {
        throw new \Exception('Method getById() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): MeterConsumption {
        throw new \Exception('Method create() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): MeterConsumption {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, MeterConsumption>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
