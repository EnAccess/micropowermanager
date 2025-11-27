<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\ApplianceFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property      int                                      $id
 * @property      string                                   $name
 * @property      int                                      $appliance_type_id
 * @property      int                                      $price
 * @property      Carbon|null                              $created_at
 * @property      Carbon|null                              $updated_at
 * @property-read Collection<int, AgentAssignedAppliances> $agentAssignedAppliance
 * @property-read ApplianceType|null                       $applianceType
 * @property-read Collection<int, AppliancePerson>         $rates
 * @property-read SmsApplianceRemindRate|null              $smsReminderRate
 */
class Appliance extends BaseModel {
    /** @use HasFactory<ApplianceFactory> */
    use HasFactory;

    public const RELATION_NAME = 'appliance';

    protected $table = 'appliances';

    /**
     * @return BelongsTo<ApplianceType, $this>
     */
    public function applianceType(): BelongsTo {
        return $this->belongsTo(ApplianceType::class, 'appliance_type_id');
    }

    /**
     * @return HasMany<AgentAssignedAppliances, $this>
     */
    public function agentAssignedAppliance(): HasMany {
        return $this->hasMany(AgentAssignedAppliances::class, 'appliance_id', 'id');
    }

    /**
     * @return HasMany<AppliancePerson, $this>
     */
    public function rates(): HasMany {
        return $this->hasMany(AppliancePerson::class, 'appliance_id', 'id');
    }

    /**
     * @return HasOne<SmsApplianceRemindRate, $this>
     */
    public function smsReminderRate(): HasOne {
        return $this->hasOne(SmsApplianceRemindRate::class, 'appliance_id', 'id');
    }
}
