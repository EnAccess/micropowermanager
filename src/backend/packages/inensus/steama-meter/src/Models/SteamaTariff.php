<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Meter\MeterTariff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SteamaTariff extends \App\Models\Base\BaseModel {
    protected $table = 'steama_tariffs';

    public function mpmTariff(): BelongsTo {
        return $this->belongsTo(MeterTariff::class, 'mpm_tariff_id');
    }
}
