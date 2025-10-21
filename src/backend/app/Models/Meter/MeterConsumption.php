<?php

namespace App\Models\Meter;

use App\Models\Base\BaseModel;
use Database\Factories\Meter\MeterConsumptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    /** @use HasFactory<MeterConsumptionFactory> */
    use HasFactory;
    protected $table = 'meter_consumptions';

    /**
     * @return BelongsTo<Meter, $this>
     */
    public function meter(): BelongsTo {
        return $this->belongsTo(Meter::class);
    }

    public function __toString(): string {
        return 'Meter  : '.$this->meter_id.'  consumption : '.$this->total_consumption.
            '  credit :'.$this->credit_on_meter;
    }
}
