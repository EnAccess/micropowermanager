<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Agent;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Agent      $mpmAgent
 * @property SteamaSite $site
 */
class SteamaAgent extends BaseModel {
    protected $table = 'steama_agents';

    public function mpmAgent(): BelongsTo {
        return $this->belongsTo(Agent::class, 'mpm_agent_id');
    }

    public function site(): BelongsTo {
        return $this->belongsTo(SteamaSite::class, 'site_id', 'site_id');
    }
}
