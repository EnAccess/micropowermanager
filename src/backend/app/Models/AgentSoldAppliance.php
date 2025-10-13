<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Database\Factories\AgentSoldApplianceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSoldAppliance extends BaseModel {
    /** @use HasFactory<AgentSoldApplianceFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<AgentAssignedAppliances, $this>
     */
    public function assignedAppliance(): BelongsTo {
        return $this->belongsTo(AgentAssignedAppliances::class, 'agent_assigned_appliance_id', 'id');
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }
}
