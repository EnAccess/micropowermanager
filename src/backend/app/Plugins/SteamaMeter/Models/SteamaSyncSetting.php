<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
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

    /**
     * @return HasOne<SteamaSyncAction, $this>
     */
    public function syncAction(): HasOne {
        return $this->hasOne(SteamaSyncAction::class);
    }

    /**
     * @return MorphOne<SteamaSetting, $this>
     */
    public function setting(): MorphOne {
        return $this->morphOne(SteamaSetting::class, 'setting');
    }
}
