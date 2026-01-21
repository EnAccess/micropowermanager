<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\MeterType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int            $id
 * @property      int            $mpm_meter_type_id
 * @property      string         $version
 * @property      string         $usage_spike_threshold
 * @property      string|null    $hash
 * @property      Carbon|null    $created_at
 * @property      Carbon|null    $updated_at
 * @property-read MeterType|null $mpmMeterType
 */
class SteamaMeterType extends BaseModel {
    protected $table = 'steama_meter_types';

    /**
     * @return BelongsTo<MeterType, $this>
     */
    public function mpmMeterType(): BelongsTo {
        return $this->belongsTo(MeterType::class, 'mpm_meter_type_id');
    }
}
