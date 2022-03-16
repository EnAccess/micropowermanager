<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsApplianceRemindRate extends BaseModel
{
    protected $table = 'sms_appliance_remind_rates';

    public function applianceType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class, 'appliance_type_id', 'id');
    }
}
