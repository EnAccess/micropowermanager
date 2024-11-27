<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Meter\MeterTariff;

class SmTariff extends BaseModel {
    protected $table = 'sm_tariffs';

    public function mpmTariff() {
        return $this->belongsTo(MeterTariff::class, 'mpm_tariff_id');
    }

    public function site() {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}
