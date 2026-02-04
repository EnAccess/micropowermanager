<?php

namespace App\Plugins\GomeLongMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Tariff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      string      $tariff_id
 * @property      int         $mpm_tariff_id
 * @property      string|null $vat
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Tariff|null $mpmTariff
 */
class GomeLongTariff extends BaseModel {
    protected $table = 'gome_long_tariffs';

    /**
     * @return BelongsTo<Tariff, $this>
     */
    public function mpmTariff(): BelongsTo {
        return $this->belongsTo(Tariff::class, 'mpm_tariff_id');
    }

    public function getTariffId(): string {
        return $this->tariff_id;
    }
}
