<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Meter\MeterTariff;

class SteamaTariff extends BaseModel {
    protected $table = 'steama_tariffs';

    public function mpmTariff() {
        return $this->belongsTo(MeterTariff::class, 'mpm_tariff_id');
    }
}
