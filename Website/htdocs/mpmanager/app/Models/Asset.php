<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends BaseModel
{
    public const RELATION_NAME = 'appliance';
    protected $table = 'assets';

    public function assetType(): HasOne
    {
        return $this->hasOne(AssetType::class, 'id', 'asset_type_id');
    }

    public function agentAssignedAppliance(): HasMany
    {
        return $this->hasMany(AgentAssignedAppliances::class, 'appliance_type_id', 'id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(AssetPerson::class);
    }

    public function smsReminderRate(): HasOne
    {
        return $this->hasOne(SmsApplianceRemindRate::class, 'appliance_id', 'id');
    }
}
