<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Agent;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int             $id
 * @property      int             $site_id
 * @property      int             $agent_id
 * @property      int             $mpm_agent_id
 * @property      bool            $is_credit_limited
 * @property      float           $credit_balance
 * @property      string|null     $hash
 * @property      Carbon|null     $created_at
 * @property      Carbon|null     $updated_at
 * @property-read Agent|null      $mpmAgent
 * @property-read SteamaSite|null $site
 */
class SteamaAgent extends BaseModel {
    protected $table = 'steama_agents';

    /**
     * @return BelongsTo<Agent, $this>
     */
    public function mpmAgent(): BelongsTo {
        return $this->belongsTo(Agent::class, 'mpm_agent_id');
    }

    /**
     * @return BelongsTo<SteamaSite, $this>
     */
    public function site(): BelongsTo {
        return $this->belongsTo(SteamaSite::class, 'site_id', 'site_id');
    }
}
