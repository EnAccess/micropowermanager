<?php

namespace Inensus\Prospect\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $sync_setting_id
 * @property int         $attempts
 * @property Carbon|null $last_sync
 * @property Carbon|null $next_sync
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProspectSyncAction extends BaseModel {
    protected $table = 'prospect_sync_actions';

    /**
     * @return BelongsTo<ProspectSyncSetting, $this>
     */
    public function synSetting(): BelongsTo {
        return $this->belongsTo(ProspectSyncSetting::class, 'sync_setting_id');
    }
}
