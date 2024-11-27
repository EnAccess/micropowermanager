<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsApplianceRemindRate extends BaseModel {
    protected $table = 'sms_appliance_remind_rates';

    public function appliance(): BelongsTo {
        return $this->belongsTo(Asset::class, 'appliance_id', 'id');
    }
}
