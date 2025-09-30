<?php

namespace App\Models;

use Database\Factories\AgentAssignedAppliancesFactory;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
