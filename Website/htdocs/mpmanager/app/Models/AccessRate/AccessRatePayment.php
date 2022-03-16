<?php

namespace App\Models\AccessRate;

use App\Models\Meter\Meter;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AccessRatePayment
 *
 * @package  App
 * @property int meter_id
 * @property int access_rate_id
 * @property DateTime due_date
 * @property int debt
 */
class AccessRatePayment extends Model
{
    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function accessRate(): BelongsTo
    {
        return $this->belongsTo(AccessRate::class);
    }
}
