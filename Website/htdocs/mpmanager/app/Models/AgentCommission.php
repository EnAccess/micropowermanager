<?php

namespace App\Models;

use App\Models\Base\BaseModel;
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
class AgentCommission extends BaseModel
{
    public const RELATION_NAME = 'agent_commission';

    public function agent(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function history(): MorphOne
    {
        return $this->morphOne(AgentBalanceHistory::class, 'trigger');
    }
}
