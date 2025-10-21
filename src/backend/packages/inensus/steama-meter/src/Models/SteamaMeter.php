<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                 $id
 * @property      int                 $meter_id
 * @property      int                 $customer_id
 * @property      int|null            $bit_harvester_id
 * @property      int                 $mpm_meter_id
 * @property      string|null         $hash
 * @property      Carbon|null         $created_at
 * @property      Carbon|null         $updated_at
 * @property-read Meter|null          $mpmMeter
 * @property-read SteamaCustomer|null $stmCustomer
 */
class SteamaMeter extends BaseModel {
    protected $table = 'steama_meters';

    public function mpmMeter(): BelongsTo {
        return $this->belongsTo(Meter::class, 'mpm_meter_id');
    }

    public function stmCustomer(): BelongsTo {
        return $this->belongsTo(SteamaCustomer::class, 'customer_id', 'customer_id');
    }
}
