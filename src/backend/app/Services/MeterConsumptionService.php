<?php

namespace App\Services;

use App\Models\Meter\Meter;
use App\Models\Meter\MeterConsumption;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<MeterConsumption>
 */
class MeterConsumptionService implements IBaseService {
    /** @use HasCrudOperations<MeterConsumption> */
    use HasCrudOperations;

    public function __construct(
        private MeterConsumption $meterConsumption,
    ) {}

    protected function crudModel(): MeterConsumption {
        return $this->meterConsumption;
    }

    /**
     * @return Collection<int, MeterConsumption>
     */
    public function getByMeter(Meter $meter, string $start, string $end): Collection {
        return $this->meterConsumption->newQuery()
            ->where('meter_id', $meter->id)->whereBetween(
                'reading_date',
                [$start, $end]
            )->oldest('reading_date')->get();
    }
}
