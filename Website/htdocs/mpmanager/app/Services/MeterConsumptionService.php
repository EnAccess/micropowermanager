<?php

namespace App\Services;

use App\Models\Meter\Meter;
use App\Models\Meter\MeterConsumption;
use App\Services\SessionService;
use Illuminate\Database\Eloquent\Collection;

class MeterConsumptionService  implements IBaseService
{
    public function __construct(private MeterConsumption $meterConsumption)
    {

    }

    public function getByMeter(Meter $meter, $start, $end): Collection|array
    {
        return $this->meterConsumption->newQuery()
            ->where('meter_id', $meter->id)->whereBetween(
                'reading_date',
                [$start, $end]
            )->orderBy('reading_date')->get();
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($data)
    {
        // TODO: Implement create() method.
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}
