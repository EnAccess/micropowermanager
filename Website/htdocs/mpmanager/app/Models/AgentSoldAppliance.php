<?php

namespace App\Models;

use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSoldAppliance extends Model
{

    protected $guarded = [];

    public function assignedAppliance(): BelongsTo
    {
        return $this->belongsTo(AgentAssignedAppliances::class, 'agent_assigned_appliance_id', 'id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
