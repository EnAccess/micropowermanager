<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int           $id
 * @property int           $mpm_meter_id
 * @property string        $meter_address
 * @property string        $meter_name
 * @property string        $customer_no
 * @property int           $rtuId
 * @property string        $hash
 * @property Carbon        $created_at
 * @property Carbon        $updated_at
 * @property Meter         $mpmMeter
 * @property KelinCustomer $kelinCustomer
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
