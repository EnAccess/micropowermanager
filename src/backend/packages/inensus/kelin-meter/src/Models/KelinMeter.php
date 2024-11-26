<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;

class KelinMeter extends BaseModel {
    protected $table = 'kelin_meters';

    public function mpmMeter() {
        return $this->belongsTo(Meter::class, 'mpm_meter_id');
    }

    public function kelinCustomer() {
        return $this->belongsTo(KelinCustomer::class, 'customer_no', 'customer_no');
    }
}
