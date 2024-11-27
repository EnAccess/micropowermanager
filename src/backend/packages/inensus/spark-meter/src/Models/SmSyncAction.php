<?php

namespace Inensus\SparkMeter\Models;

class SmSyncAction extends BaseModel {
    protected $table = 'sm_sync_actions';

    public function synSetting() {
        return $this->belongsTo(SmSyncSetting::class, 'sync_setting_id');
    }
}
