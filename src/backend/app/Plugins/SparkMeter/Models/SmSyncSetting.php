<?php

namespace App\Plugins\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property      int               $id
 * @property      string            $action_name
 * @property      string            $sync_in_value_str
 * @property      int               $sync_in_value_num
 * @property      int               $max_attempts
 * @property      Carbon|null       $created_at
 * @property      Carbon|null       $updated_at
 * @property-read SmSetting|null    $setting
 * @property-read SmSyncAction|null $syncAction
 */
class SmSyncSetting extends BaseModel {
    protected $table = 'sm_sync_settings';

    /**
     * @return HasOne<SmSyncAction, $this>
     */
    public function syncAction(): HasOne {
        return $this->hasOne(SmSyncAction::class);
    }

    /**
     * @return MorphOne<SmSetting, $this>
     */
    public function setting(): MorphOne {
        return $this->morphOne(SmSetting::class, 'setting');
    }
}
