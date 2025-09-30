<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\AgentCommissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Class AgentCommission.
 *
 * @property int   $id
 * @property float $energy_commission
 * @property float $appliance_commission
 * @property float $risk_balance
 */
class AgentCommission extends BaseModel {
    /** @use HasFactory<AgentCommissionFactory> */
    use HasFactory;

    public const RELATION_NAME = 'agent_commission';

    /**
     * @return HasMany<Agent, $this>
     */
    public function agent(): HasMany {
        return $this->hasMany(Agent::class);
    }

    /**
     * @return MorphOne<AgentBalanceHistory, $this>
     */
    public function history(): MorphOne {
        return $this->morphOne(AgentBalanceHistory::class, 'trigger');
    }
}
