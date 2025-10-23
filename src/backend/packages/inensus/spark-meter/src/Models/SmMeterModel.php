<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\MeterType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int            $id
 * @property      string         $site_id
 * @property      string         $model_name
 * @property      int            $mpm_meter_type_id
 * @property      int            $continuous_limit
 * @property      int            $inrush_limit
 * @property      string|null    $hash
 * @property      Carbon|null    $created_at
 * @property      Carbon|null    $updated_at
 * @property-read MeterType|null $meterType
 * @property-read SmSite|null    $site
 */
class SmMeterModel extends BaseModel {
    protected $table = 'sm_meter_models';

    public function meterType(): BelongsTo {
        return $this->belongsTo(MeterType::class, 'mpm_meter_type_id');
    }

    public function site(): BelongsTo {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}
