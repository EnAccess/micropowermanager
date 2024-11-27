<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Meter\MeterType;

class SteamaMeterType extends BaseModel {
    protected $table = 'steama_meter_types';

    public function mpmMeterType() {
        return $this->belongsTo(MeterType::class, 'mpm_meter_type_id');
    }
}
