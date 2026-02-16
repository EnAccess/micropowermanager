<?php

namespace App\Plugins\SparkMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Tariff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      string      $site_id
 * @property      string      $tariff_id
 * @property      int         $mpm_tariff_id
 * @property      int         $flat_load_limit
 * @property      string|null $plan_duration
 * @property      int|null    $plan_price
 * @property      string|null $hash
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Tariff|null $mpmTariff
 * @property-read SmSite|null $site
 */
class SmTariff extends BaseModel {
    protected $table = 'sm_tariffs';

    /**
     * @return BelongsTo<Tariff, $this>
     */
    public function mpmTariff(): BelongsTo {
        return $this->belongsTo(Tariff::class, 'mpm_tariff_id');
    }

    /**
     * @return BelongsTo<SmSite, $this>
     */
    public function site(): BelongsTo {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}
