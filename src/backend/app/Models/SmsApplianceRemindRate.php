<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $appliance_id
 * @property      int         $overdue_remind_rate
 * @property      int         $remind_rate
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Appliance|null  $appliance
 */
class SmsApplianceRemindRate extends BaseModel {
    protected $table = 'sms_appliance_remind_rates';

    /**
     * @return BelongsTo<Appliance, $this>
     */
    public function appliance(): BelongsTo {
        return $this->belongsTo(Appliance::class, 'appliance_id', 'id');
    }
}
