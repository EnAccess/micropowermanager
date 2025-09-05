<?php

namespace Inensus\GomeLongMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\MeterTariff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property MeterTariff $mpmTariff
 */
class GomeLongTariff extends BaseModel {
    protected $table = 'gome_long_tariffs';

    public function mpmTariff(): BelongsTo {
        return $this->belongsTo(MeterTariff::class, 'mpm_tariff_id');
    }

    public function getTariffId(): string {
        return $this->tariff_id;
    }
}
