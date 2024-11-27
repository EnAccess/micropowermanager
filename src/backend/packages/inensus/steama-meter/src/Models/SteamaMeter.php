<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Meter\Meter;

class SteamaMeter extends BaseModel {
    protected $table = 'steama_meters';

    public function mpmMeter() {
        return $this->belongsTo(Meter::class, 'mpm_meter_id');
    }

    public function stmCustomer() {
        return $this->belongsTo(SteamaCustomer::class, 'customer_id', 'customer_id');
    }
}
