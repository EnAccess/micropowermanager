<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                    $id
 * @property      int                    $sync_setting_id
 * @property      int                    $attempts
 * @property      Carbon|null            $last_sync
 * @property      Carbon|null            $next_sync
 * @property      Carbon|null            $created_at
 * @property      Carbon|null            $updated_at
 * @property-read SteamaSyncSetting|null $synSetting
 */
class SteamaSyncAction extends BaseModel {
    protected $table = 'steama_sync_actions';

    /**
     * @return BelongsTo<SteamaSyncSetting, $this>
     */
    public function synSetting(): BelongsTo {
        return $this->belongsTo(SteamaSyncSetting::class, 'sync_setting_id');
    }
}
