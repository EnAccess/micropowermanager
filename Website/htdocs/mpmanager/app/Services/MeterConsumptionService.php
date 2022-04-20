<?php

namespace App\Services;

use App\Models\Meter\Meter;
use App\Models\Meter\MeterConsumption;
use App\Services\SessionService;
use Illuminate\Database\Eloquent\Collection;

class MeterConsumptionService extends BaseService
{
    public function __construct(private MeterConsumption $meterConsumption)
    {
        parent::__construct([$meterConsumption]);
    }

    public function getByMeter(Meter $meter, $start, $end): Collection|array
    {
        return $this->meterConsumption->newQuery()
            ->where('meter_id', $meter->id)->whereBetween(
                'reading_date',
                [$start, $end]
            )->orderBy('reading_date')->get();
    }
}