<?php

namespace Inensus\SparkMeter\Models;

class SmSyncSetting extends \App\Models\Base\BaseModel {
    protected $table = 'sm_sync_settings';

    public function syncAction() {
        return $this->hasOne(SmSyncAction::class);
    }

    public function setting() {
        return $this->morphOne(SmSetting::class, 'setting');
    }
}
