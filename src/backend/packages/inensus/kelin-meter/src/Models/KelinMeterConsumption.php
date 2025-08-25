<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelinMeterConsumption extends BaseModel {
    protected $table = 'kelin_meter_consumptions';

    public function meter(): BelongsTo {
        return $this->belongsTo(Meter::class);
    }
}
