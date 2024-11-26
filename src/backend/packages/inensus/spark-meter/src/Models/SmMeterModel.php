<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Meter\MeterType;

class SmMeterModel extends BaseModel {
    protected $table = 'sm_meter_models';

    public function meterType() {
        return $this->belongsTo(MeterType::class, 'mpm_meter_type_id');
    }

    public function site() {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}
