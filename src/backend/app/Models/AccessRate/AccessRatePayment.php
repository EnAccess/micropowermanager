<?php

namespace App\Models\AccessRate;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class AccessRatePayment.
 *
 * @property      int             $id
 * @property      int             $meter_id
 * @property      int             $access_rate_id
 * @property      Carbon          $due_date
 * @property      float           $debt
 * @property      float           $unpaid_in_row
 * @property      Carbon|null     $created_at
 * @property      Carbon|null     $updated_at
 * @property-read AccessRate|null $accessRate
 * @property-read Meter|null      $meter
 */
class AccessRatePayment extends BaseModel {
    /**
     * @return BelongsTo<Meter, $this>
     */
    public function meter(): BelongsTo {
        return $this->belongsTo(Meter::class);
    }

    /**
     * @return BelongsTo<AccessRate, $this>
     */
    public function accessRate(): BelongsTo {
        return $this->belongsTo(AccessRate::class);
    }
}
