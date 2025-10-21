<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                   $id
 * @property      int                   $sync_setting_id
 * @property      int                   $attempts
 * @property      string|null           $last_sync
 * @property      string|null           $next_sync
 * @property      Carbon|null           $created_at
 * @property      Carbon|null           $updated_at
 * @property-read KelinSyncSetting|null $synSetting
 */
class KelinSyncAction extends BaseModel {
    protected $table = 'kelin_sync_actions';

    /**
     * @return BelongsTo<KelinSyncSetting, $this>
     */
    public function synSetting(): BelongsTo {
        return $this->belongsTo(KelinSyncSetting::class, 'sync_setting_id');
    }
}
