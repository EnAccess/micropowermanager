<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Tariff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $mpm_tariff_id
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Tariff|null $mpmTariff
 */
class SteamaTariff extends BaseModel {
    protected $table = 'steama_tariffs';

    /**
     * @return BelongsTo<Tariff, $this>
     */
    public function mpmTariff(): BelongsTo {
        return $this->belongsTo(Tariff::class, 'mpm_tariff_id');
    }
}
