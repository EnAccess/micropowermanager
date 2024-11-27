<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;

class KelinMeterConsumption extends BaseModel {
    protected $table = 'kelin_meter_consumptions';

    public function meter() {
        return $this->belongsTo(Meter::class);
    }
}
