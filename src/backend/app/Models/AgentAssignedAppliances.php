<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\AgentAssignedAppliancesFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                                 $id
 * @property      int                                 $agent_id
 * @property      int                                 $user_id
 * @property      int                                 $appliance_id
 * @property      float                               $cost
 * @property      Carbon|null                         $created_at
 * @property      Carbon|null                         $updated_at
 * @property-read Agent|null                          $agent
 * @property-read Asset|null                          $appliance
 * @property-read Collection<int, AgentSoldAppliance> $soldAppliance
 * @property-read User|null                           $user
 */
class AgentAssignedAppliances extends BaseModel {
    /** @use HasFactory<AgentAssignedAppliancesFactory> */
    use HasFactory;

    public const RELATION_NAME = 'agent_appliance';
    protected $guarded = [];

    /**
     * @return BelongsTo<Agent, $this>
     */
    public function agent(): BelongsTo {
        return $this->belongsTo(Agent::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function appliance(): BelongsTo {
        return $this->belongsTo(Asset::class, 'appliance_id', 'id');
    }

    /**
     * @return HasMany<AgentSoldAppliance, $this>
     */
    public function soldAppliance(): HasMany {
        return $this->hasMany(AgentSoldAppliance::class);
    }
}
