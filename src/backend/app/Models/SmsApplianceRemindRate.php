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
 * @property-read Asset|null  $appliance
 */
class SmsApplianceRemindRate extends BaseModel {
    protected $table = 'sms_appliance_remind_rates';

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function appliance(): BelongsTo {
        return $this->belongsTo(Asset::class, 'appliance_id', 'id');
    }
}
