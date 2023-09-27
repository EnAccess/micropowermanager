<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentAssignedAppliances extends BaseModel
{
    protected $guarded = [];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appliance(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'appliance_id', 'id');
    }

    public function soldAppliance(): HasMany
    {
        return $this->hasMany(AgentSoldAppliance::class);
    }
}
