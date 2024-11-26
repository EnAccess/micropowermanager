<?php

namespace App\Models\Meter;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class MeterConsumption.
 *
 * @property int    $id
 * @property int    $meter_id
 * @property float  $total_consumption
 * @property float  $consumption
 * @property float  $credit_on_meter
 * @property string $reading_date
 */
class MeterConsumption extends BaseModel {
    protected $table = 'meter_consumptions';

    public function meter(): BelongsTo {
        return $this->belongsTo(Meter::class);
    }

    public function __toString() {
        return 'Meter  : '.$this->meter_id.'  consumption : '.$this->total_consumption.
            '  credit :'.$this->credit_on_meter;
    }
}
