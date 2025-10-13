<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\AgentChargeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class AgentCharge extends BaseModel {
    /** @use HasFactory<AgentChargeFactory> */
    use HasFactory;

    public const RELATION_NAME = 'agent_charge';

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
     * @return MorphOne<AgentBalanceHistory, $this>
     */
    public function history(): MorphOne {
        return $this->morphOne(AgentBalanceHistory::class, 'trigger');
    }
}
