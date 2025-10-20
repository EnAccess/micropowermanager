<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;

class SmSyncAction extends BaseModel {
    protected $table = 'sm_sync_actions';

    public function synSetting() {
        return $this->belongsTo(SmSyncSetting::class, 'sync_setting_id');
    }
}
