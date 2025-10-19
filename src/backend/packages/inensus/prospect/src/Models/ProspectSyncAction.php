<?php

namespace Inensus\Prospect\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectSyncAction extends BaseModel {
    protected $table = 'prospect_sync_actions';

    public function synSetting(): BelongsTo {
        return $this->belongsTo(ProspectSyncSetting::class, 'sync_setting_id');
    }
}
