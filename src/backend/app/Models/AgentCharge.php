<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class AgentCharge extends BaseModel {
    use HasFactory;

    public const RELATION_NAME = 'agent_charge';

    public function agent(): BelongsTo {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function history(): MorphOne {
        return $this->morphOne(AgentBalanceHistory::class, 'trigger');
    }
}
