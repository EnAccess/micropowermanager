<?php

namespace App\Models\AccessRate;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AccessRatePayment.
 *
 * @property int       $meter_id
 * @property int       $access_rate_id
 * @property \DateTime $due_date
 * @property int       $debt
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
