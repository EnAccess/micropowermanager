<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;

class KelinSyncAction extends BaseModel {
    protected $table = 'kelin_sync_actions';

    public function synSetting() {
        return $this->belongsTo(KelinSyncSetting::class, 'sync_setting_id');
    }
}
