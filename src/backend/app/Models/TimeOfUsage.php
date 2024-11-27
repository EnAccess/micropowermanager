<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\MeterTariff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeOfUsage extends BaseModel {
    public function tariff(): BelongsTo {
        return $this->belongsTo(MeterTariff::class);
    }
}
