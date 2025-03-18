<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentAssignedAppliances extends BaseModel {
    use HasFactory;
    public const RELATION_NAME = 'agent_appliance';
    protected $guarded = [];

    public function agent(): BelongsTo {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function appliance(): BelongsTo {
        return $this->belongsTo(Asset::class, 'appliance_id', 'id');
    }

    public function soldAppliance(): HasMany {
        return $this->hasMany(AgentSoldAppliance::class);
    }
}
