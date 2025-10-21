<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\MeterTariff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int              $id
 * @property      int              $mpm_tariff_id
 * @property      Carbon|null      $created_at
 * @property      Carbon|null      $updated_at
 * @property-read MeterTariff|null $mpmTariff
 */
class SteamaTariff extends BaseModel {
    protected $table = 'steama_tariffs';

    public function mpmTariff(): BelongsTo {
        return $this->belongsTo(MeterTariff::class, 'mpm_tariff_id');
    }
}
