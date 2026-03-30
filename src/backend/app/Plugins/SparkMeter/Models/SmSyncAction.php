<?php

namespace App\Plugins\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                $id
 * @property      int                $sync_setting_id
 * @property      int                $attempts
 * @property      Carbon|null        $last_sync
 * @property      Carbon|null        $next_sync
 * @property      Carbon|null        $created_at
 * @property      Carbon|null        $updated_at
 * @property-read SmSyncSetting|null $synSetting
 */
class SmSyncAction extends BaseModel {
    protected $table = 'sm_sync_actions';

    /**
     * @return BelongsTo<SmSyncSetting, $this>
     */
    public function synSetting(): BelongsTo {
        return $this->belongsTo(SmSyncSetting::class, 'sync_setting_id');
    }
}
