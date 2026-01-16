<?php

namespace App\Plugins\KelinMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Meter|null $meter
 */
class KelinMeterConsumption extends BaseModel {
    protected $table = 'kelin_meter_consumptions';

    /**
     * @return BelongsTo<Meter, $this>
     */
    public function meter(): BelongsTo {
        return $this->belongsTo(Meter::class);
    }
}
