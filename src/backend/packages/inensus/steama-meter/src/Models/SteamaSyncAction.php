<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property      int                    $id
 * @property      int                    $sync_setting_id
 * @property      int                    $attempts
 * @property      string|null            $last_sync
 * @property      string|null            $next_sync
 * @property      Carbon|null            $created_at
 * @property      Carbon|null            $updated_at
 * @property-read SteamaSyncSetting|null $synSetting
 */
class SteamaSyncAction extends BaseModel {
    protected $table = 'steama_sync_actions';

    public function synSetting() {
        return $this->belongsTo(SteamaSyncSetting::class, 'sync_setting_id');
    }
}
