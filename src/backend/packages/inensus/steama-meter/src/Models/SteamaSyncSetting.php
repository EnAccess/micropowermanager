<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property      int                   $id
 * @property      string                $action_name
 * @property      string                $sync_in_value_str
 * @property      int                   $sync_in_value_num
 * @property      int                   $max_attempts
 * @property      Carbon|null           $created_at
 * @property      Carbon|null           $updated_at
 * @property-read SteamaSetting|null    $setting
 * @property-read SteamaSyncAction|null $syncAction
 */
class SteamaSyncSetting extends BaseModel {
    protected $table = 'steama_sync_settings';

    public function syncAction() {
        return $this->hasOne(SteamaSyncAction::class);
    }

    public function setting() {
        return $this->morphOne(SteamaSetting::class, 'setting');
    }
}
