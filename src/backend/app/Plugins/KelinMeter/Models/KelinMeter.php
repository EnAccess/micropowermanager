<?php

namespace App\Plugins\KelinMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                $id
 * @property      int                $mpm_meter_id
 * @property      string             $meter_address
 * @property      string             $meter_name
 * @property      string             $customer_no
 * @property      int                $rtuId
 * @property      string|null        $hash
 * @property      Carbon|null        $created_at
 * @property      Carbon|null        $updated_at
 * @property-read KelinCustomer|null $kelinCustomer
 * @property-read Meter|null         $mpmMeter
 */
class KelinMeter extends BaseModel {
    protected $table = 'kelin_meters';

    /**
     * @return BelongsTo<Meter, $this>
     */
    public function mpmMeter(): BelongsTo {
        return $this->belongsTo(Meter::class, 'mpm_meter_id');
    }

    /**
     * @return BelongsTo<KelinCustomer, $this>
     */
    public function kelinCustomer(): BelongsTo {
        return $this->belongsTo(KelinCustomer::class, 'customer_no', 'customer_no');
    }
}
