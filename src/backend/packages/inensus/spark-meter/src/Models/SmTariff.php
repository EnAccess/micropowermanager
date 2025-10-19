<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Meter\MeterTariff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property MeterTariff $mpmTariff
 * @property SmSite      $site
 */
class SmTariff extends \App\Models\Base\BaseModel {
    protected $table = 'sm_tariffs';

    public function mpmTariff(): BelongsTo {
        return $this->belongsTo(MeterTariff::class, 'mpm_tariff_id');
    }

    public function site(): BelongsTo {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}
