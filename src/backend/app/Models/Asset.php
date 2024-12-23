<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends BaseModel {
    use HasFactory;

    public const RELATION_NAME = 'appliance';
    protected $table = 'assets';

    public function assetType(): BelongsTo {
        return $this->belongsTo(AssetType::class);
    }

    public function agentAssignedAppliance(): HasMany {
        return $this->hasMany(AgentAssignedAppliances::class, 'appliance_id', 'id');
    }

    public function rates(): HasMany {
        return $this->hasMany(AssetPerson::class);
    }

    public function smsReminderRate(): HasOne {
        return $this->hasOne(SmsApplianceRemindRate::class, 'appliance_id', 'id');
    }
}
