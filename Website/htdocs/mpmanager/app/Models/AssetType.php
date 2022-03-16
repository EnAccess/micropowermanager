<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssetType extends BaseModel
{

    public function agentAssignedAppliance(): HasMany
    {
        return $this->hasMany(AgentAssignedAppliances::class, 'id', 'appliance_type_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(AssetPerson::class);
    }

    public function smsReminderRate(): HasOne
    {
        return $this->hasOne(SmsApplianceRemindRate::class, 'appliance_type_id', 'id');
    }
}
