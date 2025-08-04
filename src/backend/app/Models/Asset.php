<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory>
 */
class Asset extends BaseModel {
    /** @use HasFactory<\Database\Factories\AssetFactory> */
    use HasFactory;

    public const RELATION_NAME = 'appliance';
    protected $table = 'assets';

    /**
     * @return BelongsTo<AssetType, $this>
     */
    public function assetType(): BelongsTo {
        return $this->belongsTo(AssetType::class);
    }

    /**
     * @return HasMany<AgentAssignedAppliances, $this>
     */
    public function agentAssignedAppliance(): HasMany {
        return $this->hasMany(AgentAssignedAppliances::class, 'appliance_id', 'id');
    }

    /**
     * @return HasMany<AssetPerson, $this>
     */
    public function rates(): HasMany {
        return $this->hasMany(AssetPerson::class);
    }

    /**
     * @return HasOne<SmsApplianceRemindRate, $this>
     */
    public function smsReminderRate(): HasOne {
        return $this->hasOne(SmsApplianceRemindRate::class, 'appliance_id', 'id');
    }
}
