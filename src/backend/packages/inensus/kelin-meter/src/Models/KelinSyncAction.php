<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelinSyncAction extends BaseModel {
    protected $table = 'kelin_sync_actions';

    /**
     * @return BelongsTo<KelinSyncSetting, $this>
     */
    public function synSetting(): BelongsTo {
        return $this->belongsTo(KelinSyncSetting::class, 'sync_setting_id');
    }
}
