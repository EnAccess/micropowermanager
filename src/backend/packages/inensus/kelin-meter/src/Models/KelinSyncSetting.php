<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class KelinSyncSetting extends BaseModel {
    protected $table = 'kelin_sync_settings';

    public function syncAction(): HasOne {
        return $this->hasOne(KelinSyncAction::class);
    }

    public function setting(): MorphOne {
        return $this->morphOne(KelinSetting::class, 'setting');
    }
}
