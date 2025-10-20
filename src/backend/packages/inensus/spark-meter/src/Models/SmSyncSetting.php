<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;

class SmSyncSetting extends BaseModel {
    protected $table = 'sm_sync_settings';

    public function syncAction() {
        return $this->hasOne(SmSyncAction::class);
    }

    public function setting() {
        return $this->morphOne(SmSetting::class, 'setting');
    }
}
