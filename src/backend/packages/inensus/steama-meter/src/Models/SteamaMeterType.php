<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\MeterType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SteamaMeterType extends BaseModel {
    protected $table = 'steama_meter_types';

    public function mpmMeterType(): BelongsTo {
        return $this->belongsTo(MeterType::class, 'mpm_meter_type_id');
    }
}
