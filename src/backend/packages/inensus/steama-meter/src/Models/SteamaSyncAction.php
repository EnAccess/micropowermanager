<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;

class SteamaSyncAction extends BaseModel {
    protected $table = 'steama_sync_actions';

    public function synSetting() {
        return $this->belongsTo(SteamaSyncSetting::class, 'sync_setting_id');
    }
}
