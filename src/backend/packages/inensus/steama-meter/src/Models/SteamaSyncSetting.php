<?php

namespace Inensus\SteamaMeter\Models;

class SteamaSyncSetting extends BaseModel {
    protected $table = 'steama_sync_settings';

    public function syncAction() {
        return $this->hasOne(SteamaSyncAction::class);
    }

    public function setting() {
        return $this->morphOne(SteamaSetting::class, 'setting');
    }
}
