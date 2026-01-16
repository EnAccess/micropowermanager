<?php

namespace App\Plugins\GomeLongMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\MeterTariff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int              $id
 * @property      string           $tariff_id
 * @property      int              $mpm_tariff_id
 * @property      string|null      $vat
 * @property      Carbon|null      $created_at
 * @property      Carbon|null      $updated_at
 * @property-read MeterTariff|null $mpmTariff
 */
class GomeLongTariff extends BaseModel {
    protected $table = 'gome_long_tariffs';

    /**
     * @return BelongsTo<MeterTariff, $this>
     */
    public function mpmTariff(): BelongsTo {
        return $this->belongsTo(MeterTariff::class, 'mpm_tariff_id');
    }

    public function getTariffId(): string {
        return $this->tariff_id;
    }
}
