<?php

namespace Inensus\GomeLongMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\MeterTariff;

class GomeLongTariff extends BaseModel {
    protected $table = 'gome_long_tariffs';

    public function mpmTariff() {
        return $this->belongsTo(MeterTariff::class, 'mpm_tariff_id');
    }

    public function getTariffId() {
        return $this->tariff_id;
    }
}
