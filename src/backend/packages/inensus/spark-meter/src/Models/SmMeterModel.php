<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Meter\MeterType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmMeterModel extends \App\Models\Base\BaseModel {
    protected $table = 'sm_meter_models';

    public function meterType(): BelongsTo {
        return $this->belongsTo(MeterType::class, 'mpm_meter_type_id');
    }

    public function site(): BelongsTo {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}
