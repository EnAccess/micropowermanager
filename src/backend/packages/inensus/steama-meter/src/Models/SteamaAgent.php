<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Agent;

class SteamaAgent extends BaseModel {
    protected $table = 'steama_agents';

    public function mpmAgent() {
        return $this->belongsTo(Agent::class, 'mpm_agent_id');
    }

    public function site() {
        return $this->belongsTo(SteamaSite::class, 'site_id', 'site_id');
    }
}
