<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Meter\Meter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SteamaMeter extends BaseModel {
    protected $table = 'steama_meters';

    public function mpmMeter(): BelongsTo {
        return $this->belongsTo(Meter::class, 'mpm_meter_id');
    }

    public function stmCustomer(): BelongsTo {
        return $this->belongsTo(SteamaCustomer::class, 'customer_id', 'customer_id');
    }
}
