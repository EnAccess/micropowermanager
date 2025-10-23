<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\AgentCommissionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * Class AgentCommission.
 *
 * @property      int                      $id
 * @property      string                   $name
 * @property      float                    $energy_commission
 * @property      float                    $appliance_commission
 * @property      float                    $risk_balance
 * @property      Carbon|null              $created_at
 * @property      Carbon|null              $updated_at
 * @property-read Collection<int, Agent>   $agent
 * @property-read AgentBalanceHistory|null $history
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
