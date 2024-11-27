<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;

class KelinSyncSetting extends BaseModel {
    protected $table = 'kelin_sync_settings';

    public function syncAction() {
        return $this->hasOne(KelinSyncAction::class);
    }

    public function setting() {
        return $this->morphOne(KelinSetting::class, 'setting');
    }
}
